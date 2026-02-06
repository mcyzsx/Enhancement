CREATE TABLE IF NOT EXISTS `typecho_photos` (
  `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '图片表主键',
  `group_name` varchar(100) DEFAULT 'default' COMMENT '分组名称',
  `group_display` varchar(200) DEFAULT NULL COMMENT '分组显示名称',
  `url` varchar(500) NOT NULL COMMENT '图片链接',
  `cover` varchar(500) DEFAULT NULL COMMENT '封面链接',
  `title` varchar(200) DEFAULT NULL COMMENT '图片标题',
  `description` text DEFAULT NULL COMMENT '图片描述',
  `order` int(10) UNSIGNED DEFAULT '0' COMMENT '排序',
  `created_at` int(10) UNSIGNED DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`pid`),
  KEY `group_name` (`group_name`),
  KEY `order` (`order`)
) ENGINE=MYISAM DEFAULT CHARSET=%charset%;
