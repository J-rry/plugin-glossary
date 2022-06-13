Ext.define('Plugin.glossary.g_data_props', {

    extend: 'Ext.Window',

    closeAction: 'hide',
    title: '',
    width: 650,
    height: 300,
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
            height: 200,
            defaults: {bodyStyle: 'background:none; padding:5px'},
            items: [{
                title: _('Термин'),
                layout: 'form',
                defaults: {anchor: '0'},
                defaultType: 'textfield',
                items: [
                    {
                        fieldLabel: _('Термин'),
                        name: 'term',
                        allowBlank: false
                    }, {
                        fieldLabel: _('Определение'),
                        name: 'specification',
                        allowBlank: false
                    }, {
                        fieldLabel: _('Синонимы'),
                        name: 'synonyms',
                        allowBlank: true
                    }, {
                        fieldLabel: _('Ссылки'),
                        name: 'links',
                        allowBlank: true
                    },
                ]
            }]
        });

        this.form = new Ext.FormPanel({
            labelWidth: 140,
            border: false,
            width: 638,
            bodyStyle: 'background: none',
            method: 'POST',
            waitMsgTarget: true,
            url: '/plugins/glossary/g_list_actions.php',
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
        if (id > 0) {
            Ext.Ajax.request({
                url: '/plugins/glossary/g_list_actions.php',
                params: {
                    action: 'get_g_list',
                    id: this.listId
                },
                scope: this,
                success: function (resp) {
                    var obj = Ext.decode(resp.responseText);
                    this.setTitle(_('Изменить термин'));
                    this.form.getForm().setValues(obj.data);
                }
            });
        } else {
            this.setTitle(_('Новый термин'));
        }
    },

    submit: function () {

        var params = {
            action: 'save_term',
            id: this.listId,
        };
        this.form.getForm().submit({
            params: params,
            scope: this,
            waitMsg: _('Сохранение...'),
            success: function (resp) {
                this.fireEvent('listChanged', this.listId, this.form.getForm().findField('term').getValue());
                this.hide();
            }
        });
    }
});