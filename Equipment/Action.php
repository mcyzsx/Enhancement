<?php
/**
 * 装备管理 Action
 *
 * @package Enhancement
 */
class Enhancement_Equipment_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 数据库对象
     */
    private $db;

    /**
     * 表前缀
     */
    private $prefix;

    /**
     * 构造函数
     */
    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * 添加装备
     */
    public function insert()
    {
        $this->checkPermission();

        $data = $this->prepareData();
        $data['order'] = intval($this->request->get('order', 0));

        try {
            $this->db->query($this->db->insert($this->prefix . 'equipment')->rows($data));
            $this->widget('Widget_Notice')->set('装备添加成功', 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set('装备添加失败: ' . $e->getMessage(), 'error');
        }

        $this->response->redirect(Typecho_Common::url('extending.php?panel=Enhancement%2Fmanage-equipment.php', $this->options->adminUrl));
    }

    /**
     * 更新装备
     */
    public function update()
    {
        $this->checkPermission();

        $eid = intval($this->request->get('eid'));
        if (!$eid) {
            $this->widget('Widget_Notice')->set('装备ID无效', 'error');
            $this->response->redirect(Typecho_Common::url('extending.php?panel=Enhancement%2Fmanage-equipment.php', $this->options->adminUrl));
            return;
        }

        $data = $this->prepareData();
        $data['order'] = intval($this->request->get('order', 0));

        try {
            $this->db->query($this->db->update($this->prefix . 'equipment')->rows($data)->where('eid = ?', $eid));
            $this->widget('Widget_Notice')->set('装备更新成功', 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set('装备更新失败: ' . $e->getMessage(), 'error');
        }

        $this->response->redirect(Typecho_Common::url('extending.php?panel=Enhancement%2Fmanage-equipment.php', $this->options->adminUrl));
    }

    /**
     * 删除装备
     */
    public function delete()
    {
        $this->checkPermission();

        $eid = intval($this->request->get('eid'));
        if (!$eid) {
            $this->widget('Widget_Notice')->set('装备ID无效', 'error');
            $this->response->redirect(Typecho_Common::url('extending.php?panel=Enhancement%2Fmanage-equipment.php', $this->options->adminUrl));
            return;
        }

        try {
            $this->db->query($this->db->delete($this->prefix . 'equipment')->where('eid = ?', $eid));
            $this->widget('Widget_Notice')->set('装备删除成功', 'success');
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set('装备删除失败: ' . $e->getMessage(), 'error');
        }

        $this->response->redirect(Typecho_Common::url('extending.php?panel=Enhancement%2Fmanage-equipment.php', $this->options->adminUrl));
    }

    /**
     * 准备数据
     */
    private function prepareData()
    {
        $name = trim($this->request->get('name'));
        $categroy = trim($this->request->get('categroy', '硬件'));
        $desc = trim($this->request->get('desc'));
        $image = trim($this->request->get('image'));
        $src = trim($this->request->get('src'));
        $date = trim($this->request->get('date'));
        $money = intval($this->request->get('money', 0));

        // 解析 JSON 数据
        $info = $this->request->get('info');
        $tag = $this->request->get('tag');

        // 验证并格式化 JSON
        $infoArray = json_decode($info, true);
        if (!is_array($infoArray)) {
            $infoArray = [];
        }

        $tagArray = json_decode($tag, true);
        if (!is_array($tagArray)) {
            $tagArray = [];
        }

        return [
            'name' => $name,
            'categroy' => $categroy,
            'desc' => $desc,
            'image' => $image,
            'src' => $src,
            'info' => json_encode($infoArray, JSON_UNESCAPED_UNICODE),
            'tag' => json_encode($tagArray, JSON_UNESCAPED_UNICODE),
            'date' => $date,
            'money' => $money
        ];
    }

    /**
     * 检查权限
     */
    private function checkPermission()
    {
        if (!$this->user->pass('administrator')) {
            throw new Typecho_Widget_Exception(_t('没有权限'));
        }
    }

    /**
     * 入口函数
     */
    public function action()
    {
        $this->on($this->request->is('do=insert'))->insert();
        $this->on($this->request->is('do=update'))->update();
        $this->on($this->request->is('do=delete'))->delete();
    }
}
