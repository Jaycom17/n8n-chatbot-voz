from odoo import models, fields, api

class ConversationStage(models.Model):
    _name = 'conversation.stage'
    _description = 'Etapas de Conversación'
    _order = 'sequence'

    name = fields.Char(string='Nombre de la Etapa', required=True)
    description = fields.Text(string='Descripción')
    sequence = fields.Integer(string='Secuencia', default=10)
    message_ids = fields.One2many(
        'whatsapp.message.template',
        'conversation_stage_id',
        string='Mensajes'
    )
    message_count = fields.Integer(
        string='Cantidad de Mensajes',
        compute='_compute_message_count'
    )

    @api.depends('message_ids')
    def _compute_message_count(self):
        for record in self:
            record.message_count = len(record.message_ids)