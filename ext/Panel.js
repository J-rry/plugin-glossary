Ext.define('Plugin.glossary.Panel', {
  extend: 'Ext.tab.Panel',

  requires: ['Plugin.glossary.g_data_grid', 'Plugin.glossary.g_options_grid'],

  bodyCls: 'x-window-body-default',
  cls: 'x-window-body-default',
  style: 'border: none',
  border: false,
  layout: 'border',

  items: [
    Ext.create('Plugin.glossary.g_data_grid', {
        'title': _('Список терминов'),
    }),
    Ext.create('Plugin.glossary.g_options_grid', {
        'title': _('Настройки'),
    })
  ]

});