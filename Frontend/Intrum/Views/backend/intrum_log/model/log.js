/**
 * $Id: $
 */

/**
 * Shopware - Log model
 *
 * This model represents a single log of s_core_log.
 */
//{block name="backend/intrum_log/model/log"}
Ext.define('Shopware.apps.IntrumLog.model.Log', {
  /**
    * Extends the standard ExtJS 4
    * @string
    */
  extend: 'Ext.data.Model',
  /**
    * The fields used for this model
    * @array
    */
  fields: [
  //{block name="backend/intrum_log/model/log/fields"}{/block}
  'id',
  'requestid',
  'requesttype',
  'firstname',
  'lastname',
  'ip',
  'status',
  'datecolumn'
  ]
  ,
  /**
    * Configure the data communication
    * @object
    */
  proxy: {
    type: 'ajax',
    /**
        * Configure the url mapping for the different
        * @object
        */
    api: {
      //read out all articles
      read: '{url controller="IntrumLog" action="getApilogs"}',
      destroy: '{url controller="IntrumLog" action="deleteLogs"}',
      detail: '{url controller="IntrumLog" action="getDetailLog"}'
    },
    /**
        * Configure the data reader
        * @object
        */
    reader: {
      type: 'json',
      root: 'data',
      //total values, used for paging
      totalProperty: 'total'
    }
  }
});
//{/block}