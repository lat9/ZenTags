$(function(){
    $('#zen-tags-remove-outer').on('click', 'a', function(e) {
        var tagData = {
            tag_id: this.id,
            tag_mapping_id: $('#tag_mapping_id').val(), 
            tag_mapping_type: $('#tag_mapping_type').val(), 
        };

        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=removeTag",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutErrorMessage);
                }
            },
        }).done(function(response) {
            $('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        })
        return false;
    });

    $('#zen-tags-add').on('click', 'button', function(e) {
       var tagData = {
            tag_id: this.id,
            tag_mapping_id: $('#tag_mapping_id').val(), 
            tag_mapping_type: $('#tag_mapping_type').val(), 
            tag_list: $('#zen-tag-input').val()
        };

        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=addTags",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
                    alert(ajaxTimeoutErrorMessage);
                }
            },
        }).done(function(response) {
            $('#zen-tag-input').val('').focus();
            $('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        });
        return false;
    });

    $('#zen-tag-cloud-outer').on('click', 'a.choose', function(e) {  
        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=makeCloud",
            data: '',
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
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

    $('#zen-tag-cloud-outer').on('click', 'a.add_tag', function(e) {
        var tagData = {
            tag_id: this.id,
            tag_mapping_id: $('#tag_mapping_id').val(), 
            tag_mapping_type: $('#tag_mapping_type').val(), 
        };

        zcJS.ajax({
            url: "ajax.php?act=ajaxTagManager&method=addTagItem",
            data: tagData,
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
                    alert(ajaxTimeoutErrorMessage);
                }
            },
        }).done(function(response) {
            $('#zen-tags-remove').replaceWith('<div id="zen-tags-remove">'+response.tag_list+'</div>');
        });
        return false;
    });
});