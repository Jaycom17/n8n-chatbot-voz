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
      console.log('Nuevo session ID creado:', sessionId);
    } else {
      console.log('Session ID existente:', sessionId);
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
    console.error('Voice Chatbot: No configurado');
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
    console.log('Boton clickeado. Estado actual:', currentState, 'Llamada activa:', callActive);
    
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
    console.log('Iniciando modo escucha continua...');
    
    try {
      stream = await navigator.mediaDevices.getUserMedia({ 
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: 44100
        } 
      });
      
      console.log('Microfono activado');
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
      console.error('Error al acceder al microfono:', error);
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
    console.log('Configurando deteccion de voz...');
    
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
      
      if (currentState === STATE.READY && volume > SILENCE_THRESHOLD) {
        startRecording();
      }
      
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
      
      requestAnimationFrame(detectVoice);
    }
    
    detectVoice();
  }

  // ============================================
  // Grabar audio
  // ============================================
  
  function startRecording() {
    if (!stream || currentState !== STATE.READY) return;
    
    console.log('Iniciando grabacion...');
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
      console.error('Error al grabar:', error);
      setState(STATE.READY);
      updateStatus('Error', error.message);
    }
  }

  function stopRecording() {
    console.log('Deteniendo grabacion...');
    
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
    console.log('Procesando audio...');
    
    if (audioChunks.length === 0) {
      setState(STATE.READY);
      updateStatus('Llamada activa', 'Esperando que hables...');
      return;
    }

    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    console.log('Audio blob creado:', audioBlob.size, 'bytes');
    
    setState(STATE.PROCESSING);
    updateStatus('Procesando...', 'Analizando tu mensaje...');

    try {
      await sendAudioToWebhook(audioBlob);
    } catch (error) {
      console.error('Error:', error);
      setState(STATE.READY);
      updateStatus('Llamada activa', 'Esperando que hables...');
    }
  }

  async function sendAudioToWebhook(audioBlob) {
    console.log('Enviando a webhook:', voiceChatbotConfig.webhookUrl);
    console.log('Session ID:', SESSION_ID);
    
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

    console.log('Respuesta del servidor:', response.status);

    if (!response.ok) {
      throw new Error('Error del servidor: ' + response.status);
    }

    const responseBlob = await response.blob();
    console.log('Audio recibido:', responseBlob.size, 'bytes');
    
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
    console.log('Reproduciendo respuesta...');
    
    audioElement.src = audioBlobUrl;
    audioElement.load();

    await new Promise(function(resolve, reject) {
      audioElement.oncanplaythrough = resolve;
      audioElement.onerror = function() {
        reject(new Error('Error al cargar el audio'));
      };
    });

    setState(STATE.SPEAKING);
    updateStatus('Asistente hablando...', 'Puedes interrumpir hablando');

    await audioElement.play();

    audioElement.onended = function() {
      if (currentState === STATE.SPEAKING) {
        finishSpeaking();
      }
    };
  }

  function stopSpeaking() {
    console.log('Interrumpiendo respuesta...');
    
    if (audioElement) {
      audioElement.pause();
      audioElement.currentTime = 0;
    }
    
    finishSpeaking();
  }

  function finishSpeaking() {
    setState(STATE.READY);
    updateStatus('Escuchando...', 'Presiona de nuevo para colgar');
  }

  // ============================================
  // Colgar llamada
  // ============================================
  
  function hangUpCall() {
    console.log('Colgando llamada...');
    
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
    
    console.log('Llamada finalizada');
  }

  // ============================================
  // Inicializacion
  // ============================================
  
  console.log('Voice Chatbot inicializado');
  updateStatus('Presiona para llamar', 'Llamada de voz con IA');
  updateButtonLabel('Llamar');

})();
