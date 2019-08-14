jQuery(document).ready(function() {
    jQuery('#zen-tags-remove-outer').on('click', 'a', function(event)
    {
        var tagData = {
            tag_id: this.id,
            tag_mapping_id: jQuery('#tag_mapping_id').val(), 
            tag_mapping_type: jQuery('#tag_mapping_type').val(), 
        };

        ztLog2Console('Calling removeTag');
        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=removeTag",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                ztLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert( ajaxTimeoutErrorMessage );
                }
            },
        }).done(function(response) {
            jQuery('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        })
        return false;
    });

    jQuery('#zen-tags-add').on ('click', 'button', function(event)
    {
       var tagData = {
            tag_id: this.id,
            tag_mapping_id: jQuery('#tag_mapping_id').val(), 
            tag_mapping_type: jQuery('#tag_mapping_type').val(), 
            tag_list: jQuery('#zen-tag-input').val()
        };

        ztLog2Console('Calling addTags');
        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=addTags",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                ztLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert( ajaxTimeoutErrorMessage );
                }
            },
        }).done(function(response) {
            jQuery('#zen-tag-input').val('').focus();
            jQuery('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        });
        return false;
    });

    jQuery('#zen-tag-cloud-outer').on('click', 'a.choose', function(event)
    {  
        ztLog2Console('Calling makeCloud');
        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=makeCloud",
            data: '',
            error: function (jqXHR, textStatus, errorThrown) {
                ztLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert( ajaxTimeoutErrorMessage );
                }
            },
        }).done(function(response) {
            var tag_title = response.tag_title;
            for (var i = 0, n = response.tag_list.length, tag_list = ''; i < n; i++) {
                tag_list += ' <span class="zenTag"><a href="#" class="add_tag" id="tag_id['+response.tag_list[i]['tag_id']+']" style="font-size: '+response.tag_list[i]['font_size']+';" title="'+tag_title+'">'+response.tag_list[i]['tag_name']+'</a></span>';
            }
            $('#zen-tag-cloud').replaceWith('<div id="zen-tag-cloud">'+tag_list+'</div>');
        });
        return false;
    });

    jQuery('#zen-tag-cloud-outer').on('click', 'a.add_tag', function(event)
    {
        var tagData = {
            tag_id: this.id,
            tag_mapping_id: jQuery('#tag_mapping_id').val(), 
            tag_mapping_type: jQuery('#tag_mapping_type').val(), 
        };

        ztLog2Console('Calling addTagItem');
        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=addTagItem",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                ztLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert( ajaxTimeoutErrorMessage );
                }
            },
        }).done(function(response) {
            jQuery('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        });
        return false;
    });

    function ztLog2Console(message)
    {
        if (window.console) {
            if (typeof(console.log) == 'function') {
                console.log(message);
            }
        }
    }
});