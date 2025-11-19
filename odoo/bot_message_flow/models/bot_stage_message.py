from odoo import models, fields, api
from odoo.exceptions import ValidationError


class BotStageMessage(models.Model):
    _name = 'bot.stage.message'
    _description = 'Mensajes del Bot por Etapa'
    _rec_name = 'name'

    name = fields.Char('Nombre de la Etapa', required=True, index=True)
    message = fields.Text('Mensaje a enviar', required=True)
    next_stage_id = fields.Many2one(
        'bot.stage.message',
        string='Siguiente etapa',
        ondelete='set null'
    )
    active = fields.Boolean('Activo', default=True)

    @api.constrains('next_stage_id')
    def _check_no_circular_reference(self):
        """Evitar referencias circulares entre etapas"""
        for record in self:
            if record.next_stage_id == record:
                raise ValidationError(
                    "No puedes configurar una etapa como su propia siguiente etapa"
                )
