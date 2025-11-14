{
    'name': 'WhatsApp Conversation Messages',
    'version': '1.0',
    'category': 'Sales',
    'summary': 'Gestión de mensajes de WhatsApp por etapas de conversación',
    'description': """
        Permite configurar mensajes predefinidos que el chatbot puede usar
        en momentos específicos de la conversación, organizados por 
        producto/servicio (cursos, asesorías, etc.)
    """,
    'author': 'Tu Empresa',
    'depends': ['base', 'crm'],
    'data': [
        'security/ir.model.access.xml',
        'views/conversation_stage_views.xml',
        'views/whatsapp_message_template_views.xml',
        'views/menu_views.xml',
        'data/conversation_stages_data.xml',
    ],
    'installable': True,
    'application': True,
    'auto_install': False,
}