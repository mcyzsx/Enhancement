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

// 确保装备表存在
try {
    $db->query("SELECT 1 FROM {$prefix}equipment LIMIT 1");
} catch (Exception $e) {
    // 表不存在，创建表
    $scripts = file_get_contents(__DIR__ . '/sql/equipment.sql');
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
        $eid = intval($request->get('eid', 0));
        $data = [
            'name' => trim($request->get('name')),
            'categroy' => trim($request->get('categroy', '硬件')),
            'description' => trim($request->get('desc')),
            'image' => trim($request->get('image')),
            'src' => trim($request->get('src')),
            'date' => trim($request->get('date')),
            'money' => intval($request->get('money', 0)),
            'order' => intval($request->get('order', 0)),
        ];
        
        // 处理 JSON 字段
        $info = $request->get('info');
        $tag = $request->get('tag');
        $infoArray = json_decode($info, true);
        if (!is_array($infoArray)) $infoArray = [];
        $tagArray = json_decode($tag, true);
        if (!is_array($tagArray)) $tagArray = [];
        
        $data['info'] = json_encode($infoArray, JSON_UNESCAPED_UNICODE);
        $data['tag'] = json_encode($tagArray, JSON_UNESCAPED_UNICODE);
        
        try {
            if ($do === 'insert') {
                $db->query($db->insert($prefix . 'equipment')->rows($data));
            } else {
                $db->query($db->update($prefix . 'equipment')->rows($data)->where('eid = ?', $eid));
            }
            $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-equipment.php'));
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    } elseif ($do === 'delete') {
        // 处理单个删除
        $eid = intval($request->get('eid'));
        if ($eid) {
            try {
                $db->query($db->delete($prefix . 'equipment')->where('eid = ?', $eid));
                $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-equipment.php'));
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }
        
        // 处理批量删除
        $eids = $request->get('eid');
        if (is_array($eids) && !empty($eids)) {
            try {
                foreach ($eids as $id) {
                    $db->query($db->delete($prefix . 'equipment')->where('eid = ?', intval($id)));
                }
                $response->redirect($options->adminUrl('extending.php?panel=Enhancement/manage-equipment.php'));
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }
    }
}

// 获取编辑的装备
$editEid = intval($request->get('eid', 0));
$editItem = null;
if ($editEid > 0) {
    $editItem = $db->fetchRow($db->select()->from($prefix . 'equipment')->where('eid = ?', $editEid));
}

// 获取所有装备
$equipment = $db->fetchAll($db->select()->from($prefix . 'equipment')->order($prefix . 'equipment.order', Typecho_Db::SORT_ASC));

// 获取分类列表
$categories = ['硬件', '外设', '软件', '其他'];
$existingCats = $db->fetchAll($db->select('categroy')->from($prefix . 'equipment')->group('categroy'));
foreach ($existingCats as $cat) {
    if ($cat['categroy'] && !in_array($cat['categroy'], $categories)) {
        $categories[] = $cat['categroy'];
    }
}

// 解析编辑项的 JSON
$editInfo = [];
$editTags = [];
if ($editItem) {
    $editInfo = json_decode($editItem['info'] ?: '[]', true);
    if (!is_array($editInfo)) $editInfo = [];
    $editTags = json_decode($editItem['tag'] ?: '[]', true);
    if (!is_array($editTags)) $editTags = [];
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
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=Enhancement/manage-equipment.php'); ?>"><?php _e('装备'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('options-plugin.php?config=Enhancement'); ?>"><?php _e('设置'); ?></a></li>
                </ul>
            </div>

            <!-- 装备列表 -->
            <div class="col-mb-12" role="main">
                <form method="post" name="manage_equipment" class="operate-form">
                <div class="typecho-list-operate clearfix">
                    <div class="operate">
                        <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                        <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="<?php _e('你确认要删除这些装备吗?'); ?>" href="<?php $security->index('/action/enhancement-equipment?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="typecho-table-wrap equipment-table-wrap">
                    <table class="typecho-list-table equipment-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox"> </th>
                                <th class="col-order">排序</th>
                                <th class="col-name">名称</th>
                                <th class="col-category">分类</th>
                                <th class="col-desc">描述</th>
                                <th class="col-price">价格</th>
                                <th class="col-date">日期</th>
                                <th class="col-action">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment)): ?>
                                <tr>
                                    <td colspan="8"><h6 class="typecho-list-table-title"><?php _e('没有任何装备'); ?></h6></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipment as $item): ?>
                                    <tr id="equipment-<?php echo $item['eid']; ?>" <?php echo ($editEid == $item['eid']) ? 'class="checked"' : ''; ?>>
                                        <td><input type="checkbox" value="<?php echo $item['eid']; ?>" name="eid[]"/></td>
                                        <td><?php echo $item['order']; ?></td>
                                        <td>
                                            <?php if ($item['image']): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 8px; vertical-align: middle;">
                                            <?php endif; ?>
                                            <a href="<?php echo $request->makeUriByRequest('eid=' . $item['eid']); ?>" title="点击编辑"><?php echo htmlspecialchars($item['name']); ?></a>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['categroy'] ?: '硬件'); ?></td>
                                        <td><?php echo htmlspecialchars(Typecho_Common::subStr($item['description'] ?: '', 0, 50, '...')); ?></td>
                                        <td>￥<?php echo number_format($item['money'] ?: 0); ?></td>
                                        <td><?php echo htmlspecialchars($item['date'] ?: ''); ?></td>
                                        <td>
                                            <a href="<?php echo $request->makeUriByRequest('eid=' . $item['eid']); ?>" class="btn btn-s btn-primary"><?php _e('编辑'); ?></a>
                                            <a href="<?php echo $request->makeUriByRequest('do=delete&eid=' . $item['eid']); ?>" class="btn btn-s btn-warn" onclick="return confirm('<?php _e('确定要删除这个装备吗？'); ?>');"><?php _e('删除'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form>
            </div>

            <!-- 编辑表单（放到列表下方） -->
            <div class="col-mb-12" role="form" id="equipment-form">
                <form action="<?php echo $request->makeUriByRequest(); ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <div class="typecho-mini-panel equipment-edit-panel">
                        <h3><?php echo $editItem ? _t('编辑装备') : _t('新增装备'); ?></h3>

                        <table class="equipment-form-table">
                            <tr>
                                <td class="form-label"><label for="name"><?php _e('装备名称'); ?> <span class="required">*</span></label></td>
                                <td class="form-input" colspan="5">
                                    <input type="text" id="name" name="name" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" required placeholder="例如：MacBook Pro">
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="categroy"><?php _e('分类'); ?></label></td>
                                <td class="form-input">
                                    <select id="categroy" name="categroy" class="text">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($editItem && $editItem['categroy'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="form-label"><label for="order"><?php _e('排序'); ?></label></td>
                                <td class="form-input"><input type="number" id="order" name="order" class="text" value="<?php echo $editItem ? intval($editItem['order']) : '0'; ?>" placeholder="0"></td>
                                <td class="form-label"><label for="date"><?php _e('日期'); ?></label></td>
                                <td class="form-input"><input type="text" id="date" name="date" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['date']) : date('Y-m'); ?>" placeholder="2024-01"></td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="money"><?php _e('价格'); ?></label></td>
                                <td class="form-input"><input type="number" id="money" name="money" class="text" value="<?php echo $editItem ? intval($editItem['money']) : '0'; ?>" placeholder="0"></td>
                                <td class="form-label"><label for="tag"><?php _e('标签'); ?></label></td>
                                <td class="form-input" colspan="3"><input type="text" id="tag" name="tag" class="text mono" value="<?php echo htmlspecialchars(json_encode($editTags, JSON_UNESCAPED_UNICODE)); ?>" placeholder='["标签1","标签2"]'></td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="image"><?php _e('图片'); ?></label></td>
                                <td class="form-input" colspan="5"><input type="url" id="image" name="image" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['image']) : ''; ?>" placeholder="图片链接"></td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="src"><?php _e('链接'); ?></label></td>
                                <td class="form-input" colspan="5"><input type="url" id="src" name="src" class="text" value="<?php echo $editItem ? htmlspecialchars($editItem['src']) : ''; ?>" placeholder="产品链接"></td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="desc"><?php _e('描述'); ?></label></td>
                                <td class="form-input" colspan="5">
                                    <textarea id="desc" name="desc" class="text" rows="2" placeholder="简要描述"><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="form-label"><label for="info"><?php _e('参数'); ?></label></td>
                                <td class="form-input" colspan="5">
                                    <textarea id="info" name="info" class="text mono" rows="3" placeholder='{"芯片":"M1 Pro","内存":"16GB"}'><?php echo htmlspecialchars(json_encode($editInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></textarea>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="hidden" name="do" value="<?php echo $editItem ? 'update' : 'insert'; ?>">
                            <?php if ($editItem): ?>
                                <input type="hidden" name="eid" value="<?php echo $editItem['eid']; ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><?php echo $editItem ? _t('保存修改') : _t('添加装备'); ?></button>
                            <?php if ($editItem): ?>
                                <a href="<?php echo $request->makeUriByRequest('eid=0'); ?>" class="btn"><?php _e('取消编辑'); ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
/* 装备管理表单样式 - 表格布局 */
.equipment-edit-panel {
    margin-top: 20px;
}

.equipment-edit-panel h3 {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* 表单表格样式 */
.equipment-form-table {
    width: 100%;
    border-collapse: collapse;
}

.equipment-form-table td {
    padding: 6px 4px;
    vertical-align: top;
}

.equipment-form-table .form-label {
    width: 70px;
    text-align: left;
    padding-right: 10px;
    white-space: nowrap;
}

.equipment-form-table .form-label label {
    font-size: 12px;
    color: #555;
    font-weight: 500;
}

.equipment-form-table .form-label .required {
    color: #c00;
    margin-left: 2px;
}

.equipment-form-table .form-input {
    width: auto;
}

.equipment-form-table input.text,
.equipment-form-table select.text,
.equipment-form-table textarea.text {
    width: 100%;
    box-sizing: border-box;
    padding: 5px 8px;
    border: 1px solid #d9d9d9;
    border-radius: 2px;
    font-size: 13px;
    line-height: 1.4;
}

.equipment-form-table input.text:focus,
.equipment-form-table select.text:focus,
.equipment-form-table textarea.text:focus {
    border-color: #467b96;
    outline: none;
}

.equipment-form-table textarea.text {
    resize: vertical;
    min-height: 40px;
}

.equipment-form-table textarea.mono {
    font-family: 'Monaco', 'Menlo', 'Consolas', 'Courier New', monospace;
    font-size: 12px;
}

.equipment-edit-panel p.submit {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

/* 表格样式优化 */
.equipment-table-wrap {
    overflow-x: auto;
}

.equipment-table {
    table-layout: auto;
    width: 100%;
    min-width: 800px;
}

.equipment-table th,
.equipment-table td {
    vertical-align: middle;
    word-wrap: break-word;
    white-space: normal;
}

/* 列宽设置 */
.equipment-table .col-checkbox {
    width: 30px;
}

.equipment-table .col-order {
    width: 60px;
}

.equipment-table .col-name {
    width: auto;
    min-width: 150px;
}

.equipment-table .col-category {
    width: 80px;
}

.equipment-table .col-desc {
    width: auto;
    min-width: 200px;
}

.equipment-table .col-price {
    width: 100px;
    white-space: nowrap;
}

.equipment-table .col-date {
    width: 100px;
    white-space: nowrap;
}

.equipment-table .col-action {
    width: 120px;
    white-space: nowrap;
}

.equipment-table td img {
    max-width: 40px;
    max-height: 40px;
    border-radius: 4px;
    object-fit: cover;
}

/* 名称列内容 */
.equipment-table .col-name a {
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* 选中行高亮 */
.equipment-table tr.checked {
    background-color: #fffef0;
}

/* 操作按钮间距 */
.equipment-table .btn {
    margin-right: 3px;
    margin-bottom: 3px;
}

/* 响应式调整 */
@media screen and (max-width: 768px) {
    .equipment-form-table .form-label {
        width: 60px;
        padding-right: 6px;
    }
    .equipment-form-table .form-label label {
        font-size: 11px;
    }

    /* 表格移动端适配 */
    .equipment-table {
        font-size: 12px;
        min-width: 600px;
    }

    .equipment-table th,
    .equipment-table td {
        padding: 8px 4px;
    }

    /* 隐藏描述列 */
    .equipment-table .col-desc {
        display: none;
    }

    /* 名称列 */
    .equipment-table .col-name {
        min-width: 100px;
    }

    .equipment-table .col-name a {
        max-width: 100px;
    }

    /* 操作按钮 */
    .equipment-table .col-action .btn {
        display: inline-block;
        margin: 2px;
        padding: 4px 8px;
        font-size: 11px;
    }

    /* 图片更小 */
    .equipment-table td img {
        max-width: 30px;
        max-height: 30px;
    }
}

/* 超小屏幕适配 */
@media screen and (max-width: 480px) {
    .equipment-table {
        font-size: 11px;
        min-width: 500px;
    }

    .equipment-table th,
    .equipment-table td {
        padding: 6px 2px;
    }

    /* 隐藏排序列 */
    .equipment-table .col-order {
        display: none;
    }

    /* 名称列更窄 */
    .equipment-table .col-name {
        min-width: 80px;
    }

    .equipment-table .col-name a {
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
        $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>

<?php include 'footer.php'; ?>
