from odoo import models, fields, api

class ConversationStage(models.Model):
    _name = 'conversation.stage'
    _description = 'Etapas de Conversación'
    _order = 'sequence'

    name = fields.Char(string='Nombre de la Etapa', required=True)
    code = fields.Char(
        string='Código',
        required=True,
        help='Código único que usará n8n para consultar (ej: bienvenida, presentacion_cursos, precio_asesorias)'
    )
    description = fields.Text(string='Descripción')
    sequence = fields.Integer(string='Secuencia', default=10)
    active = fields.Boolean(string='Activo', default=True)
    message_ids = fields.One2many(
        'whatsapp.message.template',
        'conversation_stage_id',
        string='Mensajes'
    )
    message_count = fields.Integer(
        string='Cantidad de Mensajes',
        compute='_compute_message_count'
    )

    _sql_constraints = [
        ('code_unique', 'unique(code)', 'El código de la etapa debe ser único')
    ]

    @api.depends('message_ids')
    def _compute_message_count(self):
        for record in self:
            record.message_count = len(record.message_ids.filtered(lambda m: m.active))