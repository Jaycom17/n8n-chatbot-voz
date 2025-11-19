{
    'name': 'Bot Message Flow',
    'version': '1.0.0',
    'summary': 'Gestión de mensajes del bot por etapas',
    'description': 'Permite editar mensajes y etapas del bot directamente desde Odoo',
    'category': 'CRM',
    'author': 'Tu Nombre o Empresa',
    'website': 'https://tudominio.com',
    'depends': ['base', 'crm'],
    'data': [
        'views/bot_stage_message_views.xml',
    ],
    'images': ['static/description/icon.png'],
    'installable': True,
    'application': False,
    'auto_install': False,
    'license': 'LGPL-3',
}