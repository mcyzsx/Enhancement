CREATE TABLE IF NOT EXISTS `typecho_equipment` (
  `eid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '装备表主键',
  `name` varchar(100) NOT NULL COMMENT '装备名称',
  `categroy` varchar(50) DEFAULT '硬件' COMMENT '装备分类',
  `description` varchar(500) DEFAULT NULL COMMENT '装备描述',
  `image` varchar(500) DEFAULT NULL COMMENT '装备图片',
  `src` varchar(500) DEFAULT NULL COMMENT '产品链接',
  `info` text DEFAULT NULL COMMENT '规格参数JSON',
  `tag` text DEFAULT NULL COMMENT '标签JSON',
  `date` varchar(50) DEFAULT NULL COMMENT '购买日期',
  `money` int(10) UNSIGNED DEFAULT '0' COMMENT '价格',
  `order` int(10) UNSIGNED DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`eid`)
) ENGINE=MYISAM DEFAULT CHARSET=%charset%;
