Ext.define('Plugin.glossary.Options', {

    extend:'Ext.Window',

    autoShow: true,
    width: 600,
    height: 500,
    layout: 'fit',
    modal: true,
    resizable: false,
    border: false,
    title: _('Настройки разделов'),

    listId: 1,
    
	items: [
		{
            itemId: 'glossary_options',
			xtype: 'form',
			defaultType: 'textfield',
			bodyCls: 'x-window-body-default', 
			bodyPadding: 5,
			border: false,
			defaults: {
				anchor: '100%',
				labelWidth: 200,
				hideEmptyLabel: false
			},			
			items: [
                {
                    fieldLabel: _('Адрес глоссария'),
                    name: 'glossary_path',
                    padding: '10 0 35 0',
                    allowBlank: true
                }, {
                    fieldLabel: _('Title глоссария'),
                    name: 'glossary_title',
                    allowBlank: true
                }, {
                    xtype     : 'textareafield',
                    fieldLabel: _('Мета Description глоссария'),
                    name: 'glossary_description',
                    allowBlank: true
                }, {
                    xtype     : 'textareafield',
                    fieldLabel: _('Мета Keywords глоссария'),
                    name: 'glossary_keywords',
                    padding: '0 0 35 0',
                    allowBlank: true
                }, {
                    fieldLabel: _('Маска title терминов'),
                    name: 'term_title_mask',
                    allowBlank: true
                }, {
                    xtype     : 'textareafield',
                    fieldLabel: _('Маска Мета Description терминов'),
                    name: 'term_description_mask',
                    allowBlank: true
                }, {
                    xtype     : 'textareafield',
                    fieldLabel: _('Маска Мета Keywords терминов'),
                    name: 'term_keywords_mask',
                    allowBlank: true
                },
			]
		}		
	],      
      
    initComponent : function() {
                
        this.buttons = [{
            text: _('Сохранить'),
            scope: this,
            handler: function() {

                var params = this.getComponent('glossary_options').getForm().getValues();
                params.action = 'save_options';
                params.id = this.listId;

                this.getComponent('glossary_options').setLoading(true);
                Ext.Ajax.request({
                    url: '/plugins/glossary/scripts/options.php',
                    method: 'POST',
                    params: params,
                    success: function(response, opts) {
                        this.getComponent('glossary_options').setLoading(false);
                        this.close();
                    },
                    scope: this
                });	

            }
        },{
            text: _('Отмена'),
            scope: this,
            handler: this.close
        }];
        
        this.callParent();
    },
    
	afterRender: function(){
		this.getComponent('glossary_options').setLoading(true);
		Ext.Ajax.request({
			url: '/plugins/glossary/scripts/options.php',
            params: {
                action: 'get_options',
                id: this.listId
            },
            scope: this,
			success: function(response, opts) {
				var obj = Ext.decode(response.responseText);
				this.getComponent('glossary_options').getForm().setValues(obj.data);
				this.getComponent('glossary_options').setLoading(false);
			}
		});
	
		this.callParent();
	}    
});
