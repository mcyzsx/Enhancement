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

$db = Typecho_Db::get();
$prefix = $db->getPrefix();

// 确保图片表存在
try {
    $db->query("SELECT 1 FROM {$prefix}photos LIMIT 1");
} catch (Exception $e) {
    // 表不存在，创建表
    $scripts = file_get_contents(__DIR__ . '/sql/photos.sql');
    $scripts = str_replace('typecho_', $prefix, $scripts);
    $scripts = str_replace('%charset%', 'utf8', $scripts);
    $scripts = explode(';', $scripts);
    foreach ($scripts as $script) {
        $script = trim($script);
        if ($script) {
            try {
                $db->query($script, Typecho_Db::WRITE);
            } catch (Exception $e) {
                // ignore
            }
        }
    }
}

// 处理表单提交
if ($request->isPost()) {
    $do = $request->get('do');
    
    if ($do === 'insert' || $do === 'update') {
        $pid = intval($request->get('pid', 0));
        $data = [
            'group_name' => trim($request->get('group_name', 'default')),
            'group_display' => trim($request->get('group_display', '')),
            'url' => trim($request->get('url')),
            'cover' => trim($request->get('cover', '')),
            'title' => trim($request->get('title', '')),
            'description' => trim($request->get('description', '')),
            'order' => intval($request->get('order', 0)),
            'created_at' => time(),
        ];
        
        try {
            if ($do === 'insert') {
                $db->query($db->insert($prefix . 'photos')->rows($data));
            } else {
                unset($data['created_at']);
                $db->query($db->update($prefix . 'photos')->rows($data)->where('pid = ?', $pid));
            }
            $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-photos.php'));
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } elseif ($do === 'delete') {
        // 处理单个删除
        $pid = intval($request->get('pid'));
        if ($pid) {
            try {
                $db->query($db->delete($prefix . 'photos')->where('pid = ?', $pid));
                $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-photos.php'));
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }
        
        // 处理批量删除
        $pids = $request->get('pid');
        if (is_array($pids) && !empty($pids)) {
            try {
                foreach ($pids as $id) {
                    $db->query($db->delete($prefix . 'photos')->where('pid = ?', intval($id)));
                }
                $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-photos.php'));
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }
    }
}

// 获取编辑的图片
$editPid = intval($request->get('pid', 0));
$editItem = null;
if ($editPid > 0) {
    $editItem = $db->fetchRow($db->select()->from($prefix . 'photos')->where('pid = ?', $editPid));
}

// 获取所有图片
$photos = $db->fetchAll($db->select()->from($prefix . 'photos')->order($prefix . 'photos.order', Typecho_Db::SORT_ASC));

// 获取分组列表
$groups = $db->fetchAll($db->select('group_name', 'group_display')
    ->from($prefix . 'photos')
    ->group('group_name')
    ->order('group_name', Typecho_Db::SORT_ASC));

$groupList = [];
foreach ($groups as $g) {
    $groupList[] = [
        'name' => $g['group_name'],
        'display' => $g['group_display'] ?: $g['group_name']
    ];
}
if (empty($groupList)) {
    $groupList[] = ['name' => 'default', 'display' => '默认分组'];
}

include 'header.php';
include 'menu.php';

// 显示错误消息
if (!empty($errorMessage)) {
    echo '<div class="message error">' . htmlspecialchars($errorMessage) . '</div>';
}
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs clearfix">
                    <li><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-enhancement.php'); ?>"><?php _e('链接'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-moments.php'); ?>"><?php _e('瞬间'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-equipment.php'); ?>"><?php _e('装备'); ?></a></li>
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-photos.php'); ?>"><?php _e('图库'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('options-plugin.php?config=Enhancement'); ?>"><?php _e('设置'); ?></a></li>
                </ul>
            </div>

            <!-- 图片列表 -->
            <div class="col-mb-12" role="main">
                <form method="post" name="manage_photos" class="operate-form">
                <div class="typecho-list-operate clearfix">
                    <div class="operate">
                        <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                        <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="<?php _e('你确认要删除这些图片吗?'); ?>" href="<?php $security->index('/action/enhancement-photos?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="typecho-table-wrap photos-table-wrap">
                    <table class="typecho-list-table photos-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox"> </th>
                                <th class="col-order">排序</th>
                                <th class="col-image">预览</th>
                                <th class="col-title">标题</th>
                                <th class="col-group">分组</th>
                                <th class="col-desc">描述</th>
                                <th class="col-action">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($photos)): ?>
                                <tr>
                                    <td colspan="7"><h6 class="typecho-list-table-title"><?php _e('没有任何图片'); ?></h6></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($photos as $item): ?>
                                    <tr id="photo-<?php echo $item['pid']; ?>" <?php echo ($editPid == $item['pid']) ? 'class="checked"' : ''; ?>>
                                        <td><input type="checkbox" value="<?php echo $item['pid']; ?>" name="pid[]"/></td>
                                        <td><?php echo $item['order']; ?></td>
                                        <td>
                                            <?php if ($item['url']): ?>
                                                <img src="<?php echo htmlspecialchars($item['url']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $request->makeUriByRequest('pid=' . $item['pid']); ?>" title="点击编辑"><?php echo htmlspecialchars($item['title'] ?: '未命名'); ?></a>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['group_display'] ?: $item['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars(Typecho_Common::subStr($item['description'] ?: '', 0, 50, '...')); ?></td>
                                        <td>
                                            <a href="<?php echo $request->makeUriByRequest('pid=' . $item['pid']); ?>" class="btn btn-s btn-primary"><?php _e('编辑'); ?></a>
                                            <a href="<?php echo $request->makeUriByRequest('do=delete&pid=' . $item['pid']); ?>" class="btn btn-s btn-warn" onclick="return confirm('<?php _e('确定要删除这张图片吗？'); ?>');"><?php _e('删除'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form>
            </div>

            <!-- 编辑表单 -->
            <div class="col-mb-12" role="form" id="photo-form">
                <form action="<?php echo $request->makeUriByRequest(); ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <div class="typecho-mini-panel photo-edit-panel">
                        <h3><?php echo $editItem ? _t('编辑图片') : _t('新增图片'); ?></h3>

                        <table class="photo-form-table">
                            <tr>
                                <td class="form-label"><label for="url"><?php _e('图片链接'); ?> <span class="required">*</span></label></td>
                                <td class="form-input" colspan="3">
                                    <input type="url" id="url" name="url" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['url']) : ''; ?>" required placeholder="https://example.com/photo.jpg">
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="cover"><?php _e('封面链接'); ?></label></td>
                                <td class="form-input" colspan="3">
                                    <input type="url" id="cover" name="cover" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['cover']) : ''; ?>" placeholder="缩略图链接（可选）">
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="title"><?php _e('标题'); ?></label></td>
                                <td class="form-input" colspan="3">
                                    <input type="text" id="title" name="title" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>" placeholder="图片标题">
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="group_name"><?php _e('分组'); ?></label></td>
                                <td class="form-input">
                                    <select id="group_name" name="group_name" class="text">
                                        <?php foreach ($groupList as $g): ?>
                                            <option value="<?php echo htmlspecialchars($g['name']); ?>" <?php echo ($editItem && $editItem['group_name'] === $g['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['display']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="form-label"><label for="order"><?php _e('排序'); ?></label></td>
                                <td class="form-input"><input type="number" id="order" name="order" class="text" value="<?php echo $editItem ? intval($editItem['order']) : '0'; ?>" placeholder="0"></td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="group_display"><?php _e('分组显示名'); ?></label></td>
                                <td class="form-input" colspan="3">
                                    <input type="text" id="group_display" name="group_display" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['group_display']) : ''; ?>" placeholder="分组显示名称（可选）">
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="description"><?php _e('描述'); ?></label></td>
                                <td class="form-input" colspan="3">
                                    <textarea id="description" name="description" class="text" rows="3" placeholder="图片描述"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="hidden" name="do" value="<?php echo $editItem ? 'update' : 'insert'; ?>">
                            <?php if ($editItem): ?>
                                <input type="hidden" name="pid" value="<?php echo $editItem['pid']; ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><?php echo $editItem ? _t('保存修改') : _t('添加图片'); ?></button>
                            <?php if ($editItem): ?>
                                <a href="<?php echo $request->makeUriByRequest('pid=0'); ?>" class="btn"><?php _e('取消编辑'); ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
/* 图库管理表单样式 - 表格布局 */
.photo-edit-panel {
    margin-top: 20px;
}

.photo-edit-panel h3 {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* 表单表格样式 */
.photo-form-table {
    width: 100%;
    border-collapse: collapse;
}

.photo-form-table td {
    padding: 6px 4px;
    vertical-align: top;
}

.photo-form-table .form-label {
    width: 90px;
    text-align: left;
    padding-right: 10px;
    white-space: nowrap;
}

.photo-form-table .form-label label {
    font-size: 12px;
    color: #555;
    font-weight: 500;
}

.photo-form-table .form-label .required {
    color: #c00;
    margin-left: 2px;
}

.photo-form-table .form-input {
    width: auto;
}

.photo-form-table input.text,
.photo-form-table select.text,
.photo-form-table textarea.text {
    width: 100%;
    box-sizing: border-box;
    padding: 5px 8px;
    border: 1px solid #d9d9d9;
    border-radius: 2px;
    font-size: 13px;
    line-height: 1.4;
    transition: border-color 0.2s;
}

.photo-form-table input.text:focus,
.photo-form-table select.text:focus,
.photo-form-table textarea.text:focus {
    border-color: #467b96;
    outline: none;
}

.photo-form-table textarea.text {
    resize: vertical;
    min-height: 50px;
}

.photo-edit-panel p.submit {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

/* 表格样式优化 */
.photos-table-wrap {
    overflow-x: auto;
}

.photos-table {
    table-layout: auto;
    width: 100%;
    min-width: 700px;
}

.photos-table th,
.photos-table td {
    vertical-align: middle;
    word-wrap: break-word;
    white-space: normal;
}

/* 列宽设置 */
.photos-table .col-checkbox {
    width: 30px;
}

.photos-table .col-order {
    width: 60px;
}

.photos-table .col-image {
    width: 80px;
}

.photos-table .col-title {
    width: auto;
    min-width: 150px;
}

.photos-table .col-group {
    width: 120px;
}

.photos-table .col-desc {
    width: auto;
    min-width: 200px;
}

.photos-table .col-action {
    width: 120px;
    white-space: nowrap;
}

.photos-table td img {
    max-width: 60px;
    max-height: 60px;
    border-radius: 4px;
    object-fit: cover;
}

/* 标题列内容 */
.photos-table .col-title a {
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 选中行高亮 */
.photos-table tr.checked {
    background-color: #fffef0;
}

/* 操作按钮间距 */
.photos-table .btn {
    margin-right: 3px;
    margin-bottom: 3px;
}

/* 响应式调整 */
@media screen and (max-width: 768px) {
    .photo-form-table .form-label {
        width: 80px;
        padding-right: 6px;
    }
    .photo-form-table .form-label label {
        font-size: 11px;
    }

    /* 表格移动端适配 */
    .photos-table {
        font-size: 12px;
        min-width: 600px;
    }

    .photos-table th,
    .photos-table td {
        padding: 8px 4px;
    }

    /* 隐藏描述列 */
    .photos-table .col-desc {
        display: none;
    }

    /* 标题列 */
    .photos-table .col-title {
        min-width: 100px;
    }

    .photos-table .col-title a {
        max-width: 100px;
    }

    /* 操作按钮 */
    .photos-table .col-action .btn {
        display: inline-block;
        margin: 2px;
        padding: 4px 8px;
        font-size: 11px;
    }

    /* 图片更小 */
    .photos-table td img {
        max-width: 40px;
        max-height: 40px;
    }
}

/* 超小屏幕适配 */
@media screen and (max-width: 480px) {
    .photos-table {
        font-size: 11px;
        min-width: 500px;
    }

    .photos-table th,
    .photos-table td {
        padding: 6px 2px;
    }

    /* 隐藏排序列 */
    .photos-table .col-order {
        display: none;
    }

    /* 标题列更窄 */
    .photos-table .col-title {
        min-width: 80px;
    }

    .photos-table .col-title a {
        max-width: 80px;
    }
}
</style>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
(function () {
    $(document).ready(function () {
        var table = $('.typecho-list-table');

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

        <?php if ($editItem): ?>
        $('.photo-edit-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>

<?php include 'footer.php'; ?>