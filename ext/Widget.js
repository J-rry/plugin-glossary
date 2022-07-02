Ext.require('Cetera.field.WidgetTemplate');
Ext.require('Cetera.field.Folder');

// Панелька виджета
Ext.define('Plugin.glossary.Widget', {
    extend: 'Cetera.widget.Widget',
    
    saveButton: true,
    
    formfields: [
			{
				xtype: 'widgettemplate',
				widget: 'Glossary'
			}
	]
    
});
