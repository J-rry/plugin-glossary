Ext.define('Plugin.glossary.g_options_props', {

    extend: 'Ext.Window',

    closeAction: 'hide',
    title: '',
    width: 650,
    height: 333,
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
            height: 333,
            defaults: {bodyStyle: 'background:none; padding:5px'},
            items: [{
                title: _('Тип обёртки для терминов'),
                layout: 'form',
                defaults: {anchor: '0'},
                defaultType: 'checkboxfield',
                items: [
                    new Ext.form.ComboBox({
                        fieldLabel: _('Тип обёртки'),
                        name: 'g_wrap_type',
                        store: new Ext.data.SimpleStore({
                            fields: ['g_wrap_type'],
                            data: [["abbr"], ["link"], ["abbr_link"]]
                        }),
                        valueField: 'g_wrap_type',
                        displayField: 'g_wrap_type',
                        mode: 'local',
                        triggerAction: 'all',
                        editable: false
                    })
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
                this.fireEvent('listChanged', this.listId, this.form.getForm().findField('g_wrap_type').getValue());
                this.hide();
            }
        });
    }
});