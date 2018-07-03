<?php if($extView = $this->getExtViewFile(__FILE__)){include $extView; return helper::cd();}?>
<?php
$module = $this->moduleName;
$method = $this->methodName;
if(!isset($config->$module->editor->$method)) return;
$editor = $config->$module->editor->$method;
$editor['id'] = explode(',', $editor['id']);
$editorLangs  = array('en' => 'en', 'zh-cn' => 'zh_CN', 'zh-tw' => 'zh_TW');
$editorLang   = isset($editorLangs[$app->getClientLang()]) ? $editorLangs[$app->getClientLang()] : 'en';

/* set uid for upload. */
$uid = uniqid('');
?>
<link rel="stylesheet" href="<?php echo $jsRoot;?>kindeditor/themes/default/default.css" />
<script src='<?php echo $jsRoot;?>kindeditor/kindeditor-min.js'></script>
<script src='<?php echo $jsRoot;?>kindeditor/lang/<?php echo $editorLang;?>.js'></script>
<script>
(function($) {
    var kuid = '<?php echo $uid;?>';
    var editor = <?php echo json_encode($editor);?>;
    var K = KindEditor;

    var bugTools =
    [ 'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic','underline', '|', 
    'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'code', 'link', '|', 'removeformat','undo', 'redo', 'fullscreen', 'source', 'about'];
    var simpleTools = 
    [ 'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic','underline', '|', 
    'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'code', 'link', '|', 'removeformat','undo', 'redo', 'fullscreen', 'source', 'about'];
    var fullTools = 
    [ 'formatblock', 'fontname', 'fontsize', 'lineheight', '|', 'forecolor', 'hilitecolor', '|', 'bold', 'italic','underline', 'strikethrough', '|',
    'justifyleft', 'justifycenter', 'justifyright', 'justifyfull', '|',
    'insertorderedlist', 'insertunorderedlist', '|',
    'emoticons', 'image', 'insertfile', 'hr', '|', 'link', 'unlink', '/',
    'undo', 'redo', '|', 'selectall', 'cut', 'copy', 'paste', '|', 'plainpaste', 'wordpaste', '|', 'removeformat', 'clearhtml','quickformat', '|',
    'indent', 'outdent', 'subscript', 'superscript', '|',
    'table', 'code', '|', 'pagebreak', 'anchor', '|', 
    'fullscreen', 'source', 'preview', 'about'];
    var editorToolsMap = {fullTools: fullTools, simpleTools: simpleTools, bugTools: bugTools};

    // Kindeditor default options
    var editorDefaults = 
    {
        cssPath: [config.themeRoot + 'zui/css/min.css'],
        width: '100%',
        height: '200px',
        filterMode: true, 
        bodyClass: 'article-content',
        urlType: 'absolute', 
        uploadJson: createLink('file', 'ajaxUpload', 'uid=' + kuid),
        allowFileManager: true,
        langType: '<?php echo $editorLang?>',
    };

    window.editor = {};
    var nextFormControl = 'input:not([type="hidden"]), textarea:not(.ke-edit-textarea), button[type="submit"], select';

    // Init kindeditor
    var setKindeditor = function(element, options)
    {
        var $editor  = $(element);
        var pasted   = false;
        options      = $.extend({}, editorDefaults, $editor.data(), options);
        var editorID = $editor.attr('id');
        if(editorID === undefined)
        {
            editorID = 'kindeditor-' + $.zui.uuid();
            $editor.attr('id', editorID);
        }
        
        var editorTool  = editorToolsMap[options.tools || editor.tools] || simpleTools;
        var placeholder = $editor.attr('placeholder') || options.placeholder || '';
        
        $.extend(options, 
        {
            items: editorTool,
            afterChange: function(){$editor.change().hide();},
            afterCreate : function()
            {
                var frame = this.edit;
                var doc   = this.edit.doc; 
                var cmd   = this.edit.cmd; 
                pasted    = true;
                if(!K.WEBKIT && !K.GECKO)
                {
                    var pasted = false;
                    $(doc.body).bind('paste', function(ev)
                    {
                        pasted = true;
                        return true;
                    });
                    setTimeout(function()
                    {
                        $(doc.body).bind('keyup', function(ev)
                        {
                            if(pasted)
                            {
                                pasted = false;
                                return true;
                            }
                            if(ev.keyCode == 86 && ev.ctrlKey) alert('<?php echo $this->lang->error->pasteImg;?>');
                        })
                    }, 10);
                }
                if(pasted && placeholder.indexOf('<?php echo $this->lang->noticePasteImg?>') < 0)
                {
                    if(placeholder) placeholder += '<br />';
                    placeholder += ' <?php echo $this->lang->noticePasteImg?>';
                }

                /* Paste in chrome.*/
                /* Code reference from http://www.foliotek.com/devblog/copy-images-from-clipboard-in-javascript/. */
                if(K.WEBKIT)
                {
                    $(doc.body).bind('paste', function(ev)
                    {
                        var $this    = $(this);
                        var original = ev.originalEvent;
                        var file     = original.clipboardData.items[0].getAsFile();
                        if(file)
                        {
                            $('#submit').attr('disabled', 'disabled');
                            $("body").click(function(){$('#submit').removeAttr('disabled');});

                            var reader = new FileReader();
                            reader.onload = function(evt) 
                            {
                                var result = evt.target.result;
                                var arr    = result.split(",");
                                var data   = arr[1]; // raw base64
                                var contentType = arr[0].split(";")[0].split(":")[1];

                                html = '<img src="' + result + '" alt="" />';
                                $.post(createLink('file', 'ajaxPasteImage', 'uid=' + kuid), {editor: html}, function(data)
                                {
                                    cmd.inserthtml(data);
                                    $('#submit').removeAttr('disabled');
                                });
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
                /* Paste in firefox and other firefox. */
                else
                {
                    K(doc.body).bind('paste', function(ev)
                    {
                        setTimeout(function()
                        {
                            var html = K(doc.body).html();
                            if(html.search(/<img src="data:.+;base64,/) > -1)
                            {
                                $('#submit').attr('disabled', 'disabled');
                                $("body").click(function(){$('#submit').removeAttr('disabled');});
                                $.post(createLink('file', 'ajaxPasteImage', 'uid=' + kuid), {editor: html}, function(data)
                                {
                                    if(data.indexOf('<img') == 0) data = '<p>' + data + '</p>';
                                    frame.html(data);
                                    $('#submit').removeAttr('disabled');
                                });
                            }
                        }, 80);
                    });
                }
                /* End */

                /* Add for placeholder. */
                $(this.edit.doc).find('body').after('<span class="kindeditor-ph" style="width:100%;color:#888; padding:5px 5px 5px 7px; background-color:transparent; position:absolute;z-index:10;top:2px;border:0;overflow:auto;resize:none; font-size:13px;"></span>');
                var $placeholder = $(this.edit.doc).find('.kindeditor-ph');
                $placeholder.html(placeholder);
                $placeholder.css('pointerEvents', 'none');
                $placeholder.click(function(){frame.doc.body.focus()});
                if(frame.html() != '') $placeholder.hide();
            },
            afterFocus: function()
            {
                var frame = this.edit;
                var $placeholder = $(frame.doc).find('.kindeditor-ph');
                if($placeholder.size() == 0)
                {
                    setTimeout(function(){$(frame.doc).find('.kindeditor-ph').hide();}, 50);
                }
                else
                {
                    $placeholder.hide();
                }
                $editor.prev('.ke-container').addClass('focus');
            },
            afterBlur: function()
            {
                this.sync();
                $editor.prev('.ke-container').removeClass('focus');
                var frame = this.edit;
                if(K(frame.doc.body).html() == '') $(frame.doc).find('.kindeditor-ph').show();
            },
            afterTab: function(id)
            {
                var $next = $editor.next(nextFormControl);
                if(!$next.length) $next = $editor.parent().next().find(nextFormControl);
                if(!$next.length) $next = $editor.parent().parent().next().find(nextFormControl);
                $next = $next.first().focus();
                var keditor = $next.data('keditor');
                if(keditor) keditor.focus();
                else if($next.hasClass('chosen')) $next.trigger('chosen:activate');
            }
        });

        try
        {
            var keditor = K.create('#' + editorID, options);
            window.editor['#'] = window.editor[editorID] = keditor;
            $editor.data('keditor', keditor);
            return keditor;
        }
        catch(e){return false;}
    };

    // Init kindeditor with jquery way
    $.fn.kindeditor = function(options)
    {
        return this.each(function()
        {
            setKindeditor(this, options);
        });
    };

    // Init all kindeditor    
    var initKindeditor = function(afterInit)
    {
        var $submitBtn = $('form :input[type=submit]');
        if($submitBtn.length)
        {
            $submitBtn.next('#uid').remove();
            $submitBtn.after("<input type='hidden' id='uid' name='uid' value=" + kuid + ">");
        }
        if($.isFunction(afterInit)) afterInit();
        $.each(editor.id, function(key, editorID)
        {
            setKindeditor('#' + editorID);
        });
    };

    // Init all kindeditors when document is ready
    $(initKindeditor);
}(jQuery));
</script>
