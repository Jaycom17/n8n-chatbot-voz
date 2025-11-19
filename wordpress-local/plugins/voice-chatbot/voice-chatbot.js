/**
 * Voice Chatbot - Modo Llamada ChatGPT
 * Conversacion automatica con deteccion de voz
 */

(function() {
  'use strict';

  // ============================================
  // Estado de la aplicacion
  // ============================================
  
  const STATE = {
    READY: 'ready',
    LISTENING: 'listening',
    PROCESSING: 'processing',
    SPEAKING: 'speaking'
  };

  let currentState = STATE.READY;
  let mediaRecorder = null;
  let audioChunks = [];
  let audioElement = null;
  let stream = null;
  let silenceTimeout = null;
  let audioContext = null;
  let analyser = null;
  let silenceDetectionActive = false;
  let callActive = false;
  let speakingLogged = false; // Flag para evitar spam de logs
  
  const SILENCE_THRESHOLD = 0.01;
  const SILENCE_DURATION = 1500;

  // ============================================
  // Elementos del DOM
  // ============================================
  
  const voiceBtn = document.getElementById('voice-btn');
  const statusIndicator = document.getElementById('status-indicator');
  const mainStatus = document.querySelector('.main-status');
  const subStatus = document.querySelector('.sub-status');
  const container = document.getElementById('voice-chatbot-container');
  const micIcon = document.querySelector('.mic-icon');
  const phoneIcon = document.querySelector('.phone-icon');
  const buttonLabel = document.querySelector('.button-label');
  const callTimer = document.getElementById('call-timer');
  const timerDisplay = document.getElementById('timer-display');
  
  audioElement = document.getElementById('bot-audio');

  // ============================================
  // Temporizador de llamada
  // ============================================
  
  let callStartTime = null;
  let timerInterval = null;

  function startCallTimer() {
    callStartTime = Date.now();
    callTimer.style.display = 'block';
    
    timerInterval = setInterval(function() {
      const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
      const minutes = Math.floor(elapsed / 60);
      const seconds = elapsed % 60;
      timerDisplay.textContent = 
        String(minutes).padStart(2, '0') + ':' + 
        String(seconds).padStart(2, '0');
    }, 1000);
  }

  function stopCallTimer() {
    if (timerInterval) {
      clearInterval(timerInterval);
      timerInterval = null;
    }
    callTimer.style.display = 'none';
    timerDisplay.textContent = '00:00';
    callStartTime = null;
  }

  // ============================================
  // Session ID para memoria de conversacion
  // ============================================
  
  function getOrCreateSessionId() {
    // Primero intentar obtener del config (viene de WordPress con user_id)
    if (voiceChatbotConfig.sessionId) {
      return voiceChatbotConfig.sessionId;
    }
    
    // Si no hay, generar uno unico por navegador y guardarlo
    let sessionId = localStorage.getItem('voiceChatbotSessionId');
    
    if (!sessionId) {
      // Generar ID unico: timestamp + random
      sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem('voiceChatbotSessionId', sessionId);
    }
    
    return sessionId;
  }
  
  const SESSION_ID = getOrCreateSessionId();

  // ============================================
  // Funciones de utilidad (DEFINIR PRIMERO)
  // ============================================
  
  function setState(newState) {
    currentState = newState;
    voiceBtn.className = newState;
    statusIndicator.className = newState;
    container.className = newState;
    
    if (newState === STATE.PROCESSING) {
      voiceBtn.style.cursor = 'not-allowed';
      voiceBtn.disabled = false;
    } else {
      voiceBtn.style.cursor = 'pointer';
      voiceBtn.disabled = false;
    }
  }

  function updateStatus(main, sub) {
    mainStatus.textContent = main;
    subStatus.textContent = sub;
  }

  function updateButtonLabel(text) {
    if (buttonLabel) {
      buttonLabel.textContent = text;
    }
  }

  // ============================================
  // Validar configuracion
  // ============================================
  
  if (!voiceChatbotConfig.hasConfig) {
    updateStatus('Plugin no configurado', 'Ve a Ajustes > Voice Chatbot');
    voiceBtn.disabled = true;
    voiceBtn.style.opacity = '0.5';
    voiceBtn.style.cursor = 'not-allowed';
    return;
  }

  if (!voiceChatbotConfig.webhookUrl) {
    updateStatus('Webhook faltante', 'Configura el webhook en ajustes');
    voiceBtn.disabled = true;
    return;
  }

  // ============================================
  // Manejador del boton principal
  // ============================================
  
  voiceBtn.addEventListener('click', handleButtonClick);

  function handleButtonClick() {
    if (!callActive) {
      // Iniciar llamada
      initContinuousListening();
    } else {
      // Colgar llamada
      hangUpCall();
    }
  }

  // ============================================
  // Modo escucha continua
  // ============================================
  
  async function initContinuousListening() {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ 
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: 44100
        } 
      });
      
      callActive = true;
      startCallTimer();
      setupVoiceDetection();
      setState(STATE.READY);
      updateStatus('Llamada activa', 'Esperando que hables...');
      updateButtonLabel('Colgar');
      voiceBtn.classList.add('call-active');
      
      // Mostrar icono de telefono (colgar)
      if (micIcon) micIcon.style.display = 'none';
      if (phoneIcon) phoneIcon.style.display = 'block';
      
    } catch (error) {
      updateStatus('Error de microfono', 'Permite el acceso al microfono');
      
      if (error.name === 'NotAllowedError') {
        alert('Debes permitir el acceso al microfono.');
      } else {
        alert('Error: ' + error.message);
      }
    }
  }

  // ============================================
  // Deteccion automatica de voz
  // ============================================
  
  function setupVoiceDetection() {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    analyser = audioContext.createAnalyser();
    const source = audioContext.createMediaStreamSource(stream);
    source.connect(analyser);
    analyser.fftSize = 2048;
    
    const bufferLength = analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);
    
    silenceDetectionActive = true;
    
    function detectVoice() {
      if (!silenceDetectionActive) return;
      
      analyser.getByteTimeDomainData(dataArray);
      
      let sum = 0;
      for (let i = 0; i < bufferLength; i++) {
        const value = (dataArray[i] - 128) / 128;
        sum += value * value;
      }
      const volume = Math.sqrt(sum / bufferLength);
      
      // Marcar flag cuando entra en SPEAKING
      if (currentState === STATE.SPEAKING && !speakingLogged) {
        speakingLogged = true;
      }
      
      // Detectar voz en estado READY para iniciar grabacion
      if (currentState === STATE.READY && volume > SILENCE_THRESHOLD) {
        startRecording();
      }
      
      // Detectar silencio durante la grabacion
      if (currentState === STATE.LISTENING) {
        if (volume < SILENCE_THRESHOLD) {
          if (!silenceTimeout) {
            silenceTimeout = setTimeout(function() {
              stopRecording();
            }, SILENCE_DURATION);
          }
        } else {
          if (silenceTimeout) {
            clearTimeout(silenceTimeout);
            silenceTimeout = null;
          }
        }
      }
      
      // Detectar voz durante reproduccion para interrumpir
      if (currentState === STATE.SPEAKING && volume > SILENCE_THRESHOLD) {
        stopSpeaking();
        speakingLogged = false;
        // Dar un pequeño delay para que se estabilice antes de empezar a grabar
        setTimeout(function() {
          if (currentState === STATE.READY) {
            startRecording();
          }
        }, 300);
      }
      
      requestAnimationFrame(detectVoice);
    }
    
    detectVoice();
  }

  // ============================================
  // Grabar audio
  // ============================================
  
  function startRecording() {
    if (!stream || currentState !== STATE.READY) return;
    
    audioChunks = [];
    
    try {
      mediaRecorder = new MediaRecorder(stream, {
        mimeType: 'audio/webm;codecs=opus'
      });

      mediaRecorder.ondataavailable = function(event) {
        if (event.data.size > 0) {
          audioChunks.push(event.data);
        }
      };

      mediaRecorder.onstop = function() {
        processRecording();
      };

      mediaRecorder.start();
      setState(STATE.LISTENING);
      updateStatus('Escuchando...', 'Habla con naturalidad');
      
    } catch (error) {
      setState(STATE.READY);
      updateStatus('Error', error.message);
    }
  }

  function stopRecording() {
    if (silenceTimeout) {
      clearTimeout(silenceTimeout);
      silenceTimeout = null;
    }
    
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
  }

  // ============================================
  // Procesar y enviar
  // ============================================
  
  async function processRecording() {
    if (audioChunks.length === 0) {
      setState(STATE.READY);
      updateStatus('Llamada activa', 'Esperando que hables...');
      return;
    }

    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    
    setState(STATE.PROCESSING);
    updateStatus('Procesando...', 'Analizando tu mensaje...');

    try {
      await sendAudioToWebhook(audioBlob);
    } catch (error) {
      setState(STATE.READY);
      updateStatus('Llamada activa', 'Esperando que hables...');
    }
  }

  async function sendAudioToWebhook(audioBlob) {
    const formData = new FormData();
    formData.append('audio', audioBlob, 'audio.webm');
    formData.append('session_id', SESSION_ID);

    const response = await fetch(voiceChatbotConfig.webhookUrl, {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + voiceChatbotConfig.jwtToken
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error('Error del servidor: ' + response.status);
    }

    const responseBlob = await response.blob();
    
    if (!responseBlob || responseBlob.size === 0) {
      throw new Error('No se recibio audio del servidor');
    }

    const audioBlobUrl = URL.createObjectURL(responseBlob);
    await playBotResponse(audioBlobUrl);
  }

  // ============================================
  // Reproducir respuesta
  // ============================================
  
  async function playBotResponse(audioBlobUrl) {
    audioElement.src = audioBlobUrl;
    audioElement.load();

    try {
      await new Promise(function(resolve, reject) {
        // Timeout de 5 segundos
        const timeout = setTimeout(function() {
          reject(new Error('Timeout esperando audio'));
        }, 5000);
        
        audioElement.oncanplaythrough = function() {
          clearTimeout(timeout);
          resolve();
        };
        
        audioElement.onerror = function(e) {
          clearTimeout(timeout);
          reject(new Error('Error al cargar el audio'));
        };
      });
    } catch (error) {
      finishSpeaking();
      return;
    }

    setState(STATE.SPEAKING);
    updateStatus('Asistente hablando...', 'Puedes interrumpir hablando');

    // NO usar await para permitir que detectVoice() continue ejecutandose
    audioElement.play().catch(function(error) {
      finishSpeaking();
    });

    // Configurar evento onended
    audioElement.onended = function() {
      if (currentState === STATE.SPEAKING) {
        finishSpeaking();
      }
    };
  }

  function stopSpeaking() {
    if (audioElement) {
      audioElement.pause();
      audioElement.currentTime = 0;
    }
    
    finishSpeaking();
  }

  function finishSpeaking() {
    speakingLogged = false; // Reset flag para el proximo ciclo
    setState(STATE.READY);
    updateStatus('Escuchando...', 'Presiona de nuevo para colgar');
  }

  // ============================================
  // Colgar llamada
  // ============================================
  
  function hangUpCall() {
    // Detener deteccion de voz
    silenceDetectionActive = false;
    
    // Detener grabacion si esta activa
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    
    // Detener audio si esta reproduciendo
    if (audioElement) {
      audioElement.pause();
      audioElement.currentTime = 0;
    }
    
    // Cerrar stream del microfono
    if (stream) {
      stream.getTracks().forEach(function(track) {
        track.stop();
      });
      stream = null;
    }
    
    // Cerrar contexto de audio
    if (audioContext) {
      audioContext.close();
      audioContext = null;
    }
    
    // Limpiar timeouts
    if (silenceTimeout) {
      clearTimeout(silenceTimeout);
      silenceTimeout = null;
    }
    
    // Resetear estado
    callActive = false;
    currentState = STATE.READY;
    voiceBtn.className = 'ready';
    voiceBtn.classList.remove('call-active');
    statusIndicator.className = 'ready';
    container.className = 'ready';
    
    // Detener temporizador
    stopCallTimer();
    
    // Mostrar icono de microfono (iniciar)
    if (micIcon) micIcon.style.display = 'block';
    if (phoneIcon) phoneIcon.style.display = 'none';
    
    updateStatus('Llamada finalizada', 'Presiona para llamar de nuevo');
    updateButtonLabel('Llamar');
  }

  // ============================================
  // Inicializacion
  // ============================================
  
  updateStatus('Presiona para llamar', 'Llamada de voz con IA');
  updateButtonLabel('Llamar');

})();
