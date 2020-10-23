<div class="content-element__content<% if $StyleVariant %> {$StyleVariant}<% end_if %>">
    <% include ElementDatawrapperTitle %>
    <div class="outer">
        <% if $IsLazy %><noscript class="loading-lazy"><% end_if %>
            <iframe
                <% if $AlternateContent %>title="{$AlternateContent.XML}"<% end_if %>
                aria-label="chart"
                id="{$DatawrapperIdAttribute.XML}"
                class="dw"
                src="{$DatawrapperURL.XML}"
                scrolling="no"
                frameborder="0"
                height="{$IframeHeight.XML}">
            </iframe>
        <% if $IsLazy %></noscript><% end_if %>
    </div>
</div>
