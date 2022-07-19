Ext.define('Plugin.glossary.g_options_grid', {

    extend: 'Ext.form.Panel',
  
    initComponent: function () {
  
        this.store = new Ext.data.JsonStore({
            autoDestroy: true,
            remoteSort: true,
            fields: ['glossary_path', 'glossary_title', 'glossary_description', 'glossary_keywords', 'term_title_mask', 'term_description_mask', 'term_keywords_mask'],
            proxy: {
                type: 'ajax',
                url: '/plugins/glossary/g_options_data.php',
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
                    id: 'tb_options_new',
                    iconCls: 'icon-edit',
                    text: _('Настройки разделов'),
                    handler: function () {
                        this.edit(1);
                    },
                    scope: this
                }, '-',
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
            this.propertiesWin = Ext.create('Plugin.glossary.g_options_props');
            this.propertiesWin.on('listChanged', function (id, name) {
                this.reload();
            }, this);
        }
        this.propertiesWin.show(id);
    },
  
    call: function (action) {
        Ext.Ajax.request({
            url: '/plugins/glossary/g_options_actions.php',
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