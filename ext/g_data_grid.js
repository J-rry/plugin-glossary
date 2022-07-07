Ext.define('Plugin.glossary.g_data_grid', {

    extend: 'Ext.grid.GridPanel',

    columns: [
        {header: "ID", width: 50, dataIndex: 'id'},
        {header: _('Термин'), width: 150, dataIndex: 'term'},
        {flex: 1, header: _('Определение'), width: 450, dataIndex: 'specification'},
        {flex: 1, header: _('Синонимы'), width: 250, dataIndex: 'synonyms'}
    ],

    selModel: {
        mode: 'SINGLE',
        listeners: {
            'selectionchange': {
                fn: function (sm) {
                    var hs = sm.hasSelection();
                    Ext.getCmp('tb_term_edit').setDisabled(!hs);
                    Ext.getCmp('tb_term_delete').setDisabled(!hs);
                },
                scope: this
            }
        }
    },

    initComponent: function () {

        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            remoteSort: true,
            fields: ['term', 'specification', 'synonyms'],
            sortInfo: {field: "ID", direction: "ASC"},
            proxy: {
                type: 'ajax',
                url: '/plugins/glossary/g_list_data.php',
                simpleSortMode: true,
                reader: {
                    root: 'rows',
                    idProperty: 'id'
                }
            }
        });

        this.tbar = new Ext.Toolbar({
            items: [
                {
                    id: 'tb_term_new',
                    iconCls: 'icon-new',
                    tooltip: _('Создать'),
                    handler: function () {
                        this.edit(0);
                    },
                    scope: this
                }, '-',
                {
                    id: 'tb_term_edit',
                    disabled: true,
                    iconCls: 'icon-edit',
                    tooltip: _('Редактировать'),
                    handler: function () {
                        this.edit(this.getSelectionModel().getSelection()[0].getId());
                    },
                    scope: this
                }, '-',
                {
                    id: 'tb_term_delete',
                    disabled: true,
                    iconCls: 'icon-delete',
                    tooltip: _('Удалить'),
                    handler: function () {
                        this.delete_list();
                    },
                    scope: this
                }, 
            ]
        });

        this.on({
            'beforedestroy': function () {
                if (this.propertiesWin) this.propertiesWin.close();
                this.propertiesWin = false;
                if (this.chooseWin) this.chooseWin.close();
                this.chooseWin = false;
            },
            'celldblclick': function () {
                this.edit(this.getSelectionModel().getSelection()[0].getId());
            },
            scope: this
        });

        this.callParent();
        this.reload();
    },

    border: false,
    loadMask: true,
    stripeRows: true,

    edit: function (id) {
        if (!this.propertiesWin) {
            this.propertiesWin = Ext.create('Plugin.glossary.g_data_props');
            this.propertiesWin.on('listChanged', function (id, name) {
                this.reload();
            }, this);
        }
        this.propertiesWin.show(id);
    },

    delete_list: function () {
        Ext.MessageBox.confirm(_('Удалить термин'), _('Вы уверены'), function (btn) {
            if (btn == 'yes') this.call('delete_term');
        }, this);
    },

    call: function (action) {
        Ext.Ajax.request({
            url: '/plugins/glossary/g_list_actions.php',
            params: {
                action: action,
                id: this.getSelectionModel().getSelection()[0].getId()
            },
            scope: this,
            success: function (resp) {
                this.store.reload();
            }
        });
    },

    reload: function () {
        this.store.load();
    }
});