<?php

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho_Plugin::factory('admin/common.php')->begin();

Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_User')->to($user);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
include 'header.php';
include 'menu.php';
?>


<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
                <div class="col-mb-12">
                    <ul class="typecho-option-tabs clearfix">
                        <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-enhancement.php'); ?>"><?php _e('链接'); ?></a></li>
                        <li><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-moments.php'); ?>"><?php _e('瞬间'); ?></a></li>
                        <li><a href="<?php $options->adminUrl('options-plugin.php?config=Enhancement'); ?>"><?php _e('设置'); ?></a></li>
                    </ul>
                </div>

                <div class="col-mb-12 col-tb-8" role="main">
                    <?php
                        $db = Typecho_Db::get();
                        $prefix = $db->getPrefix();
                        $items = $db->fetchAll($db->select()->from($prefix.'links')->order($prefix.'links.order', Typecho_Db::SORT_ASC));
                        
                        // 获取所有分类
                        $categories = array();
                        $catRows = $db->fetchAll($db->select('sort')->from($prefix.'links')->group('sort'));
                        foreach ($catRows as $row) {
                            $sort = trim((string)$row['sort']);
                            if ($sort !== '' && !in_array($sort, $categories)) {
                                $categories[] = $sort;
                            }
                        }
                        if (empty($categories)) {
                            $categories[] = '网上邻居';
                        }
                        sort($categories);
                    ?>
                    <form method="post" name="manage_categories" class="operate-form">
                    <div class="typecho-list-operate clearfix">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些记录吗?'); ?>" href="<?php $security->index('/action/enhancement-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                    <li><a lang="<?php _e('你确认要通过这些申请吗?'); ?>" href="<?php $security->index('/action/enhancement-edit?do=approve'); ?>"><?php _e('通过'); ?></a></li>
                                    <li><a lang="<?php _e('你确认要驳回这些申请吗?'); ?>" href="<?php $security->index('/action/enhancement-edit?do=reject'); ?>"><?php _e('驳回'); ?></a></li>
                                    <li class="divider"></li>
                                    <li class="dropdown-header"><?php _e('修改分类'); ?></li>
                                    <li class="category-input-item">
                                        <div class="category-input-wrapper">
                                            <input type="text" 
                                                   id="category-input" 
                                                   class="category-input" 
                                                   placeholder="<?php _e('输入分类名称...'); ?>" 
                                                   autocomplete="off"
                                                   data-categories="<?php echo htmlspecialchars(json_encode($categories), ENT_QUOTES, 'UTF-8'); ?>">
                                            <div id="category-suggestions" class="category-suggestions"></div>
                                            <div id="category-status" class="category-status"></div>
                                        </div>
                                        <button type="button" id="category-confirm-btn" class="btn btn-s category-confirm-btn" disabled>
                                            <?php _e('确认'); ?>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="15"/>
                                <col width="25%"/>
                                <col width=""/>
                                <col width="15%"/>
                                <col width="10%"/>
                                <col width="12%"/>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th> </th>
                                    <th><?php _e('友链名称'); ?></th>
                                    <th><?php _e('友链地址'); ?></th>
                                    <th><?php _e('分类'); ?></th>
                                    <th><?php _e('图片'); ?></th>
                                    <th><?php _e('审核'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items)): $alt = 0;?>
                                <?php foreach ($items as $item): ?>
                                <tr id="enhancement-<?php echo (int)$item['lid']; ?>">
                                    <td><input type="checkbox" value="<?php echo (int)$item['lid']; ?>" name="lid[]"/></td>
                                    <td><a href="<?php echo htmlspecialchars($request->makeUriByRequest('lid=' . (int)$item['lid']), ENT_QUOTES, 'UTF-8'); ?>" title="<?php _e('点击编辑'); ?>"><?php echo htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <td><?php echo htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$item['sort'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php
                                        if ($item['image']) {
                                            $safeImage = htmlspecialchars((string)$item['image'], ENT_QUOTES, 'UTF-8');
                                            $safeName = htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8');
                                            echo '<a href="' . $safeImage . '" title="' . _t('点击放大') . '" target="_blank"><img class="avatar" src="' . $safeImage . '" alt="' . $safeName . '" width="32" height="32"/></a>';
                                        } else {
                                            $options = Typecho_Widget::widget('Widget_Options');
                                            $nopic_url = Typecho_Common::url('usr/plugins/Enhancement/nopic.png', $options->siteUrl);
                                            echo '<img class="avatar" src="'.$nopic_url.'" alt="NOPIC" width="32" height="32"/>';
                                        }
                                    ?></td>
                                    <td><?php
                                        if ($item['state'] == 1) {
                                            echo '已通过';
                                        } else {
                                            echo '待审核';
                                        }
                                    ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何记录'); ?></h6></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>
                </div>
                <style>
                    /* 下拉菜单整体美化 */
                    .dropdown-menu {
                        min-width: 200px;
                        padding: 6px 0;
                        border-radius: 6px;
                        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                        border: 1px solid #e8e8e8;
                    }
                    
                    .dropdown-menu > li > a {
                        padding: 8px 16px;
                        font-size: 13px;
                        color: #333;
                        transition: all 0.15s ease;
                    }
                    
                    .dropdown-menu > li > a:hover {
                        background-color: #f5f5f5;
                        color: #467b96;
                    }
                    
                    .dropdown-menu .divider {
                        margin: 8px 0;
                        background-color: #e8e8e8;
                    }
                    
                    .dropdown-menu .dropdown-header {
                        padding: 6px 16px;
                        font-size: 12px;
                        font-weight: 600;
                        color: #888;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    /* 分类输入区域样式 */
                    .category-input-item {
                        padding: 12px 16px !important;
                        white-space: normal !important;
                        background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
                        border-top: 1px solid #e8e8e8;
                        margin-top: 4px;
                    }
                    
                    .category-input-wrapper {
                        position: relative;
                        display: flex;
                        flex-direction: column;
                        gap: 8px;
                    }
                    
                    .category-input {
                        width: 100%;
                        padding: 8px 12px;
                        border: 1px solid #d9d9d9;
                        border-radius: 4px;
                        font-size: 13px;
                        line-height: 1.5;
                        color: #333;
                        background-color: #fff;
                        transition: all 0.2s ease;
                        box-sizing: border-box;
                    }
                    
                    .category-input:focus {
                        outline: none;
                        border-color: #467b96;
                        box-shadow: 0 0 0 3px rgba(70, 123, 150, 0.1);
                    }
                    
                    .category-input.category-exists {
                        border-color: #52c41a;
                        background-color: #f6ffed;
                    }
                    
                    .category-input.category-new {
                        border-color: #1890ff;
                        background-color: #e6f7ff;
                    }
                    
                    .category-suggestions {
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        margin-top: 4px;
                        background: #fff;
                        border: 1px solid #d9d9d9;
                        border-radius: 4px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 1000;
                        max-height: 160px;
                        overflow-y: auto;
                        display: none;
                    }
                    
                    .category-suggestions.show {
                        display: block;
                    }
                    
                    .category-suggestion-item {
                        padding: 8px 12px;
                        cursor: pointer;
                        font-size: 13px;
                        color: #333;
                        transition: all 0.15s ease;
                        border-bottom: 1px solid #f0f0f0;
                    }
                    
                    .category-suggestion-item:last-child {
                        border-bottom: none;
                    }
                    
                    .category-suggestion-item:hover,
                    .category-suggestion-item.active {
                        background-color: #f0f7ff;
                        color: #1890ff;
                    }
                    
                    .category-suggestion-item strong {
                        color: #1890ff;
                        font-weight: 600;
                    }
                    
                    .category-status {
                        font-size: 11px;
                        min-height: 16px;
                        padding: 2px 0;
                        transition: all 0.2s ease;
                        font-weight: 500;
                    }
                    
                    .category-status.exists {
                        color: #52c41a;
                    }
                    
                    .category-status.new {
                        color: #1890ff;
                    }
                    
                    .category-status.error {
                        color: #ff4d4f;
                    }
                    
                    .category-confirm-btn {
                        width: 100%;
                        padding: 8px 12px;
                        font-size: 13px;
                        font-weight: 500;
                        color: #fff;
                        background: linear-gradient(135deg, #467b96 0%, #3a6a82 100%);
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        box-shadow: 0 2px 4px rgba(70, 123, 150, 0.2);
                    }
                    
                    .category-confirm-btn:hover:not(:disabled) {
                        background: linear-gradient(135deg, #3a6a82 0%, #2e5a6e 100%);
                        box-shadow: 0 4px 8px rgba(70, 123, 150, 0.3);
                        transform: translateY(-1px);
                    }
                    
                    .category-confirm-btn:disabled {
                        background: #d9d9d9;
                        cursor: not-allowed;
                        box-shadow: none;
                    }
                    
                    /* 响应式适配 */
                    @media screen and (max-width: 768px) {
                        .dropdown-menu {
                            min-width: 180px;
                        }
                        
                        .category-input-item {
                            padding: 16px !important;
                        }
                        
                        .category-input {
                            padding: 12px 14px;
                            font-size: 16px;
                            min-height: 48px;
                            border-radius: 6px;
                        }
                        
                        .category-suggestion-item {
                            padding: 14px;
                            font-size: 15px;
                            min-height: 48px;
                        }
                        
                        .category-confirm-btn {
                            padding: 14px;
                            font-size: 15px;
                            min-height: 48px;
                            border-radius: 6px;
                        }
                        
                        .category-status {
                            font-size: 13px;
                            min-height: 20px;
                        }
                    }
                    
                    /* 动画效果 */
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(-8px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .category-suggestions.show {
                        animation: fadeIn 0.2s ease;
                    }
                    
                    @keyframes slideDown {
                        from { opacity: 0; transform: translateY(-10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .dropdown-menu {
                        animation: slideDown 0.2s ease;
                    }
                </style>
                <div class="col-mb-12 col-tb-4" role="form">
                    <?php Enhancement_Plugin::form()->render(); ?>
                </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
$('input[name="email"]').blur(function() {
    var _email = $(this).val();
    var _image = $('input[name="image"]').val();
    if (_email != '' && _image == '') {
        var k = "<?php $security->index('/action/enhancement-edit'); ?>";
        $.post(k, {"do": "email-logo", "type": "json", "email": $(this).val()}, function (result) {
            var k = jQuery.parseJSON(result).url;
            $('input[name="image"]').val(k);
        });
    }
    return false;
});
</script>
<script type="text/javascript">
(function () {
    $(document).ready(function () {
        var table = $('.typecho-list-table').tableDnD({
            onDrop : function () {
                var ids = [];

                $('input[type=checkbox]', table).each(function () {
                    ids.push($(this).val());
                });

                $.post('<?php $security->index('/action/enhancement-edit?do=sort'); ?>',
                    $.param({lid : ids}));

                $('tr', table).each(function (i) {
                    if (i % 2) {
                        $(this).addClass('even');
                    } else {
                        $(this).removeClass('even');
                    }
                });
            }
        });

        table.tableSelectable({
            checkEl     :   'input[type=checkbox]',
            rowEl       :   'tr',
            selectAllEl :   '.typecho-table-select-all',
            actionEl    :   '.dropdown-menu a'
        });

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });

        $('.dropdown-menu button.merge').click(function () {
            var btn = $(this);
            btn.parents('form').attr('action', btn.attr('rel')).submit();
        });

        // 分类输入功能
        (function() {
            var categoryInput = $('#category-input');
            var suggestionsBox = $('#category-suggestions');
            var statusBox = $('#category-status');
            var confirmBtn = $('#category-confirm-btn');
            var form = $('form[name="manage_categories"]');
            
            // 获取现有分类列表
            var existingCategories = [];
            try {
                existingCategories = JSON.parse(categoryInput.data('categories') || '[]');
            } catch(e) {
                existingCategories = [];
            }
            
            var selectedIndex = -1;
            var filteredCategories = [];
            
            // 检查分类是否存在
            function checkCategory(value) {
                var trimmed = $.trim(value);
                if (!trimmed) {
                    categoryInput.removeClass('category-exists category-new');
                    statusBox.removeClass('exists new error').text('');
                    confirmBtn.prop('disabled', true);
                    return;
                }
                
                var exists = existingCategories.some(function(cat) {
                    return cat.toLowerCase() === trimmed.toLowerCase();
                });
                
                categoryInput.removeClass('category-exists category-new');
                statusBox.removeClass('exists new error');
                
                if (exists) {
                    categoryInput.addClass('category-exists');
                    statusBox.addClass('exists').text('<?php _e('已有分类：将分配至现有分类'); ?>');
                } else {
                    categoryInput.addClass('category-new');
                    statusBox.addClass('new').text('<?php _e('新分类：将创建并分配'); ?>');
                }
                
                confirmBtn.prop('disabled', false);
            }
            
            // 显示建议
            function showSuggestions(value) {
                var trimmed = $.trim(value).toLowerCase();
                filteredCategories = [];
                selectedIndex = -1;
                
                if (!trimmed) {
                    suggestionsBox.removeClass('show').empty();
                    return;
                }
                
                // 过滤匹配的分类
                filteredCategories = existingCategories.filter(function(cat) {
                    return cat.toLowerCase().indexOf(trimmed) !== -1;
                });
                
                if (filteredCategories.length === 0) {
                    suggestionsBox.removeClass('show').empty();
                    return;
                }
                
                // 渲染建议列表
                var html = filteredCategories.map(function(cat, index) {
                    var highlighted = cat.replace(
                        new RegExp('(' + escapeRegex(trimmed) + ')', 'gi'),
                        '<strong>$1</strong>'
                    );
                    return '<div class="category-suggestion-item" data-index="' + index + '" data-value="' + escapeHtml(cat) + '">' + highlighted + '</div>';
                }).join('');
                
                suggestionsBox.html(html).addClass('show');
            }
            
            // 转义正则特殊字符
            function escapeRegex(str) {
                return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
            
            // 转义HTML
            function escapeHtml(str) {
                return str.replace(/&/g, '&amp;')
                          .replace(/</g, '&lt;')
                          .replace(/>/g, '&gt;')
                          .replace(/"/g, '&quot;');
            }
            
            // 选择建议
            function selectSuggestion(index) {
                if (index >= 0 && index < filteredCategories.length) {
                    categoryInput.val(filteredCategories[index]);
                    checkCategory(filteredCategories[index]);
                    suggestionsBox.removeClass('show');
                    selectedIndex = -1;
                }
            }
            
            // 输入事件
            categoryInput.on('input', function() {
                var value = $(this).val();
                checkCategory(value);
                showSuggestions(value);
            });
            
            // 键盘导航
            categoryInput.on('keydown', function(e) {
                if (!suggestionsBox.hasClass('show')) return;
                
                switch(e.keyCode) {
                    case 38: // 上箭头
                        e.preventDefault();
                        selectedIndex = Math.max(0, selectedIndex - 1);
                        updateActiveSuggestion();
                        break;
                    case 40: // 下箭头
                        e.preventDefault();
                        selectedIndex = Math.min(filteredCategories.length - 1, selectedIndex + 1);
                        updateActiveSuggestion();
                        break;
                    case 13: // Enter
                        e.preventDefault();
                        if (selectedIndex >= 0) {
                            selectSuggestion(selectedIndex);
                        } else if ($.trim(categoryInput.val())) {
                            confirmBtn.click();
                        }
                        break;
                    case 27: // ESC
                        suggestionsBox.removeClass('show');
                        selectedIndex = -1;
                        break;
                }
            });
            
            // 更新活动建议样式
            function updateActiveSuggestion() {
                suggestionsBox.find('.category-suggestion-item').removeClass('active');
                if (selectedIndex >= 0) {
                    suggestionsBox.find('.category-suggestion-item[data-index="' + selectedIndex + '"]').addClass('active');
                }
            }
            
            // 点击建议
            suggestionsBox.on('click', '.category-suggestion-item', function() {
                var value = $(this).data('value');
                categoryInput.val(value);
                checkCategory(value);
                suggestionsBox.removeClass('show');
            });
            
            // 点击外部关闭建议
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.category-input-wrapper').length) {
                    suggestionsBox.removeClass('show');
                }
            });
            
            // 确认按钮点击
            confirmBtn.on('click', function() {
                var category = $.trim(categoryInput.val());
                if (!category) {
                    statusBox.addClass('error').text('<?php _e('请输入分类名称'); ?>');
                    categoryInput.focus();
                    return;
                }
                
                // 检查是否有选中的链接
                var selected = form.find('input[type="checkbox"][name="lid[]"]:checked');
                if (selected.length === 0) {
                    alert('<?php _e('请先选择要修改分类的链接'); ?>');
                    return;
                }
                
                var exists = existingCategories.some(function(cat) {
                    return cat.toLowerCase() === category.toLowerCase();
                });
                
                var confirmMsg = exists 
                    ? '<?php _e('你确认要将选中的 '); ?>' + selected.length + '<?php _e(' 个链接分配至现有分类「'); ?>' + category + '<?php _e('」吗？'); ?>'
                    : '<?php _e('你确认要创建新分类「'); ?>' + category + '<?php _e('」并将选中的 '); ?>' + selected.length + '<?php _e(' 个链接分配至此分类吗？'); ?>';
                
                if (confirm(confirmMsg)) {
                    var actionUrl = '<?php $security->index('/action/enhancement-edit?do=update-category'); ?>' + '&category=' + encodeURIComponent(category);
                    form.attr('action', actionUrl);
                    form.submit();
                }
            });
            
            // 下拉菜单打开时聚焦输入框
            $('.btn-drop').on('click', function() {
                setTimeout(function() {
                    if ($('.dropdown-menu').is(':visible')) {
                        categoryInput.focus();
                    }
                }, 100);
            });
        })();

        <?php if (isset($request->lid)): ?>
        $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>
<?php include 'footer.php'; ?>

<?php /** Enhancement */ ?>
