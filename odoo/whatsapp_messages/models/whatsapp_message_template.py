from odoo import models, fields, api
from odoo.exceptions import ValidationError

class WhatsAppMessageTemplate(models.Model):
    _name = 'whatsapp.message.template'
    _description = 'Plantillas de Mensajes de WhatsApp por Conversación'
    _order = 'conversation_stage_id, sequence'

    name = fields.Char(string='Nombre de la Plantilla', required=True)
    
    # Etapa de conversación
    conversation_stage_id = fields.Many2one(
        'conversation.stage',
        string='Etapa de Conversación',
        required=True,
        help='Momento de la conversación donde se usa este mensaje'
    )
    
    message = fields.Text(
        string='Mensaje',
        required=True,
        help='Contenido del mensaje. Usa variables: {nombre}, {telefono}, {email}, {empresa}'
    )
    
    sequence = fields.Integer(string='Secuencia', default=10)
    
    # Información adicional
    notes = fields.Text(string='Notas Internas')

    def get_message_for_conversation(self, stage_name, lead_data=None):
        """
        Método principal para que n8n obtenga mensajes
        
        Args:
            stage_name: Nombre de la etapa de conversación (ej: 'Bienvenida Inicial')
            lead_data: Diccionario con datos del lead para reemplazar variables
        
        Returns:
            dict con el mensaje y metadata, o False si no hay mensaje
        """
        # Buscar la etapa de conversación por nombre
        stage = self.env['conversation.stage'].search([
            ('name', '=ilike', stage_name)
        ], limit=1)
        
        if not stage:
            return False
        
        # Buscar plantilla
        template = self.search([
            ('conversation_stage_id', '=', stage.id)
        ], order='sequence', limit=1)
        
        if not template:
            return False
        
        message = template.message
        
        # Reemplazar variables si se proporciona data
        if lead_data:
            try:
                # Reemplazar usando format con valores por defecto
                format_dict = {
                    'nombre': lead_data.get('nombre', ''),
                    'email': lead_data.get('email', ''),
                    'telefono': lead_data.get('telefono', ''),
                    'empresa': lead_data.get('empresa', ''),
                }
                # Agregar cualquier otra variable que venga en lead_data
                format_dict.update({k: v for k, v in lead_data.items() if k not in format_dict})
                
                message = message.format(**format_dict)
            except KeyError as e:
                # Si falta alguna variable, devolver el mensaje sin reemplazar
                pass
        
        return {
            'message': message,
            'template_id': template.id,
            'template_name': template.name,
            'stage_name': stage.name
        }

    def get_all_messages_for_stage(self, stage_name):
        """
        Obtiene todos los mensajes disponibles para una etapa
        Útil para que n8n vea todas las opciones
        """
        stage = self.env['conversation.stage'].search([
            ('name', '=ilike', stage_name)
        ], limit=1)
        
        if not stage:
            return []
        
        templates = self.search([
            ('conversation_stage_id', '=', stage.id)
        ], order='sequence')
        
        return [{
            'id': t.id,
            'name': t.name,
            'message': t.message,
            'sequence': t.sequence
        } for t in templates]