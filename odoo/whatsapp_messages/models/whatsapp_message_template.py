from odoo import models, fields, api
from odoo.exceptions import ValidationError
import re

class WhatsAppMessageTemplate(models.Model):
    _name = 'whatsapp.message.template'
    _description = 'Plantillas de Mensajes de WhatsApp por Conversación'
    _order = 'conversation_stage_id, product_category_id, sequence'

    name = fields.Char(string='Nombre de la Plantilla', required=True)
    
    # Etapa de conversación
    conversation_stage_id = fields.Many2one(
        'conversation.stage',
        string='Etapa de Conversación',
        required=True,
        help='Momento de la conversación donde se usa este mensaje'
    )
    
    # Categoría de producto/servicio (usando stage_id de CRM como categorías)
    product_category_id = fields.Many2one(
        'crm.stage',
        string='Producto/Servicio',
        help='Ej: Cursos, Asesorías, Consultorías'
    )
    
    message = fields.Text(
        string='Mensaje',
        required=True,
        help='Contenido del mensaje. Usa variables: {nombre}, {telefono}, {email}, {empresa}'
    )
    
    sequence = fields.Integer(string='Secuencia', default=10)
    active = fields.Boolean(string='Activo', default=True)
    
    # Información adicional
    notes = fields.Text(string='Notas Internas')
    
    # Variables detectadas automáticamente
    detected_variables = fields.Char(
        string='Variables en el Mensaje',
        compute='_compute_detected_variables',
        store=True
    )

    @api.depends('message')
    def _compute_detected_variables(self):
        for record in self:
            if record.message:
                # Detectar variables tipo {variable}
                variables = re.findall(r'\{(\w+)\}', record.message)
                record.detected_variables = ', '.join(set(variables)) if variables else 'Ninguna'
            else:
                record.detected_variables = 'Ninguna'

    def get_message_for_conversation(self, stage_code, product_category=None, lead_data=None):
        """
        Método principal para que n8n obtenga mensajes
        
        Args:
            stage_code: Código de la etapa de conversación (ej: 'bienvenida', 'presentacion_cursos')
            product_category: ID o nombre de la categoría de producto (opcional)
            lead_data: Diccionario con datos del lead para reemplazar variables
        
        Returns:
            dict con el mensaje y metadata, o False si no hay mensaje
        """
        # Buscar la etapa de conversación
        stage = self.env['conversation.stage'].search([
            ('code', '=', stage_code),
            ('active', '=', True)
        ], limit=1)
        
        if not stage:
            return False
        
        # Construir dominio de búsqueda
        domain = [
            ('conversation_stage_id', '=', stage.id),
            ('active', '=', True)
        ]
        
        # Si se especifica categoría de producto
        if product_category:
            # Intentar buscar por ID o por nombre
            if isinstance(product_category, int):
                domain.append(('product_category_id', '=', product_category))
            else:
                category = self.env['crm.stage'].search([
                    ('name', '=ilike', product_category)
                ], limit=1)
                if category:
                    domain.append(('product_category_id', '=', category.id))
        
        # Buscar plantilla
        template = self.search(domain, order='sequence', limit=1)
        
        if not template:
            # Si no hay mensaje específico para la categoría, buscar uno genérico
            template = self.search([
                ('conversation_stage_id', '=', stage.id),
                ('product_category_id', '=', False),
                ('active', '=', True)
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
            'stage_code': stage_code,
            'stage_name': stage.name,
            'product_category': template.product_category_id.name if template.product_category_id else None,
            'variables': template.detected_variables
        }

    def get_all_messages_for_stage(self, stage_code):
        """
        Obtiene todos los mensajes disponibles para una etapa
        Útil para que n8n vea todas las opciones
        """
        stage = self.env['conversation.stage'].search([
            ('code', '=', stage_code),
            ('active', '=', True)
        ], limit=1)
        
        if not stage:
            return []
        
        templates = self.search([
            ('conversation_stage_id', '=', stage.id),
            ('active', '=', True)
        ], order='sequence')
        
        return [{
            'id': t.id,
            'name': t.name,
            'message': t.message,
            'product_category': t.product_category_id.name if t.product_category_id else None,
            'sequence': t.sequence
        } for t in templates]