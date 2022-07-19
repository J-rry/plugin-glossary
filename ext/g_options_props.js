Ext.define('Plugin.glossary.g_options_props', {

    extend: 'Ext.Window',

    closeAction: 'hide',
    title: '',
    width: 900,
    height: 280,
    layout: 'vbox',
    modal: true,
    resizable: false,
    border: false,

    listId: 0,

    initComponent: function () {

        this.tabs = new Ext.TabPanel({
            deferredRender: false,
            activeTab: 0,
            plain: true,
            border: false,
            activeTab: 0,
            bodyStyle: 'background: none',
            height: 230,
            defaults: {bodyStyle: 'background:none; padding:5px'},
            items: [{
                title: _('Настройки разделов'),
                layout: 'form',
                defaults: {anchor: '0'},
                defaultType: 'textfield',
                items: [
                    {
                        fieldLabel: _('Адрес глоссария'),
                        name: 'glossary_path',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Title глоссария'),
                        name: 'glossary_title',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Description глоссария'),
                        name: 'glossary_description',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Keywords глоссария'),
                        name: 'glossary_keywords',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Маска title терминов'),
                        name: 'term_title_mask',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Маска description терминов'),
                        name: 'term_description_mask',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Маска keywords терминов'),
                        name: 'term_keywords_mask',
                        allowBlank: true
                    },
                ]
            }]
        });

        this.form = new Ext.FormPanel({
            fieldDefaults: {
                labelAlign: 'right',
                labelWidth: 180
            },
            border: false,
            width: 890,
            bodyStyle: 'background: none',
            method: 'POST',
            waitMsgTarget: true,
            url: '/plugins/glossary/g_options_actions.php',
            items: this.tabs
        });

        this.items = this.form;

        this.buttons = [{
            text: _('Ок'),
            scope: this,
            handler: this.submit
        }, {
            text: _('Отмена'),
            scope: this,
            handler: function () {
                this.hide();
            }
        }];

        this.callParent();
    },

    show: function (id) {
        this.form.getForm().reset();
        this.tabs.setActiveTab(0);

        this.callParent();

        this.listId = id;
        Ext.Ajax.request({
            url: '/plugins/glossary/g_options_actions.php',
            params: {
                action: 'get_g_options',
                id: this.listId
            },
            scope: this,
            success: function (resp) {
                var obj = Ext.decode(resp.responseText);
                this.setTitle(_('Изменить'));
                this.form.getForm().setValues(obj.data);
            }
        });
    },

    submit: function () {

        var params = {
            action: 'save_g_options',
            id: this.listId,
        };
        this.form.getForm().submit({
            params: params,
            scope: this,
            waitMsg: _('Сохранение...'),
            success: function (resp) {
                this.fireEvent('listChanged', this.listId, this.form.getForm().findField('glossary_path').getValue());
                this.hide();
            }
        });
    }
}); 