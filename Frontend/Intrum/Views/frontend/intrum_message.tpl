{block name='frontend_index_breadcrumb' prepend}
    {if $messageIntrum != ''}
        {include file="frontend/_includes/messages.tpl" type="error" content="$messageIntrum"}
    {/if}
{/block}