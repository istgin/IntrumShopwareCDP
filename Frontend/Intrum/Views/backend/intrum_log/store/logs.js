/**
 * $Id: $
 */

/**
 * Shopware - Logs store
 *
 * This store contains all logs.
 */
//{block name="backend/intrum_log/store/logs"}
Ext.define('Shopware.apps.IntrumLog.store.Logs', {
  /**
   * Extend for the standard ExtJS 4
   * @string
   */
  extend: 'Ext.data.Store',
  /**
   * Auto load the store after the component
   * is initialized
   * @boolean
   */
  autoLoad: false,
  storeId: 'logsStore',
  /**
   * Amount of data loaded at once
   * @integer
   */
  pageSize: 20,
  remoteFilter: true,
  remoteSort: true,
  /**
   * Define the used model for this store
   * @string
   */
  model: 'Shopware.apps.IntrumLog.model.Log',
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
      detail: '{url controller="IntrumLog" action="getDetailLog"}',
      search: '{url controller="IntrumLog" action="getSearchResult"}',
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
    },
    sortOnLoad: true,
    sorters: {
      property: 'creationDate',
      direction: 'DESC'
    }
  },
  // Default sorting for the store

});
//{/block}