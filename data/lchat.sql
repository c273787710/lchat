/*
Navicat MySQL Data Transfer

Source Server         : im
Source Server Version : 50718
Source Host           : 111.230.237.121:3306
Source Database       : lchat

Target Server Type    : MYSQL
Target Server Version : 50718
File Encoding         : 65001

Date: 2018-05-29 10:15:54
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for im_admin
-- ----------------------------
DROP TABLE IF EXISTS `im_admin`;
CREATE TABLE `im_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_name` varchar(155) NOT NULL DEFAULT '' COMMENT '登录名',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号码',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` varchar(155) NOT NULL DEFAULT '' COMMENT '登录密码',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '最后登录ip',
  `avatar` varchar(155) NOT NULL DEFAULT '' COMMENT '头像',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '昵称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='后台管理员表';

-- ----------------------------
-- Table structure for im_area
-- ----------------------------
DROP TABLE IF EXISTS `im_area`;
CREATE TABLE `im_area` (
  `id` int(10) NOT NULL COMMENT 'ID',
  `area_name` varchar(50) NOT NULL COMMENT '栏目名',
  `parent_id` int(10) NOT NULL COMMENT '父栏目',
  `level` tinyint(1) NOT NULL,
  `sort` tinyint(3) unsigned DEFAULT '50' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_blacktab
-- ----------------------------
DROP TABLE IF EXISTS `im_blacktab`;
CREATE TABLE `im_blacktab` (
  `user_id` int(11) NOT NULL COMMENT '操作人的id',
  `put_uid` int(11) NOT NULL COMMENT '被加入黑名单的id',
  `addtime` int(10) NOT NULL COMMENT '执行时间',
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_chatlog
-- ----------------------------
DROP TABLE IF EXISTS `im_chatlog`;
CREATE TABLE `im_chatlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) NOT NULL COMMENT '会话来源id',
  `from_name` varchar(155) NOT NULL DEFAULT '' COMMENT '消息来源用户名',
  `from_avatar` varchar(155) NOT NULL DEFAULT '' COMMENT '来源的用户头像',
  `to_id` int(11) NOT NULL COMMENT '会话发送的id',
  `content` text NOT NULL COMMENT '发送的内容',
  `timeline` int(10) NOT NULL COMMENT '记录时间',
  `type` varchar(55) NOT NULL COMMENT '聊天类型',
  `need_send` tinyint(1) DEFAULT '0' COMMENT '0 不需要推送 1 需要推送',
  PRIMARY KEY (`id`),
  KEY `fromid` (`from_id`) USING BTREE,
  KEY `toid` (`to_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_comment
-- ----------------------------
DROP TABLE IF EXISTS `im_comment`;
CREATE TABLE `im_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '评论的id',
  `blog_id` int(11) NOT NULL COMMENT '关联的说说id',
  `com_user` varchar(155) NOT NULL COMMENT '评论人名',
  `com_uid` int(11) NOT NULL COMMENT '评论人用户id',
  `com_avatar` varchar(255) NOT NULL COMMENT '评论人头像',
  `content` text NOT NULL COMMENT '评论内容',
  `com_time` int(10) NOT NULL COMMENT '评论时间',
  PRIMARY KEY (`id`),
  KEY `blog_id` (`blog_id`) USING BTREE,
  KEY `com_uid` (`com_uid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_friends
-- ----------------------------
DROP TABLE IF EXISTS `im_friends`;
CREATE TABLE `im_friends` (
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `friend_id` int(11) NOT NULL COMMENT '朋友id',
  `group_id` int(11) NOT NULL COMMENT '朋友所属组别id',
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_group
-- ----------------------------
DROP TABLE IF EXISTS `im_group`;
CREATE TABLE `im_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(155) NOT NULL COMMENT '群组名称',
  `avatar` varchar(155) NOT NULL COMMENT '群组头像',
  `owner_name` varchar(155) NOT NULL COMMENT '群主名称',
  `owner_id` int(11) NOT NULL COMMENT '群主id',
  `owner_avatar` varchar(155) NOT NULL COMMENT '群主头像',
  `owner_sign` varchar(155) NOT NULL COMMENT '群主签名',
  `addtime` int(11) NOT NULL COMMENT '添加时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1审核通过 -1待审核 -2审核不通过',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_groupdetail
-- ----------------------------
DROP TABLE IF EXISTS `im_groupdetail`;
CREATE TABLE `im_groupdetail` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(155) NOT NULL,
  `user_avatar` varchar(155) NOT NULL,
  `user_sign` varchar(155) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_mblog
-- ----------------------------
DROP TABLE IF EXISTS `im_mblog`;
CREATE TABLE `im_mblog` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '说说的id',
  `post_user` varchar(155) NOT NULL COMMENT '发表人名',
  `post_uid` int(11) NOT NULL,
  `post_avatar` varchar(255) NOT NULL COMMENT '发表人的头像',
  `content` text NOT NULL COMMENT '发表内容',
  `post_time` int(10) NOT NULL COMMENT '发表时间',
  PRIMARY KEY (`id`),
  KEY `post_uid` (`post_uid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_message
-- ----------------------------
DROP TABLE IF EXISTS `im_message`;
CREATE TABLE `im_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '信息的id',
  `content` varchar(255) NOT NULL COMMENT '消息内容',
  `uid` int(11) NOT NULL COMMENT '接收人用户id',
  `from` int(11) NOT NULL COMMENT '发送人用户id',
  `from_group` int(11) NOT NULL DEFAULT '0' COMMENT '默认消息来源群组id',
  `type` tinyint(1) NOT NULL COMMENT '消息类型 1用户消息 2系统消息',
  `remark` varchar(255) NOT NULL COMMENT '附加消息',
  `href` varchar(255) DEFAULT NULL COMMENT '消息跳转',
  `read` tinyint(1) DEFAULT '1' COMMENT '消息阅读状态 1未读 2已读',
  `time` int(11) NOT NULL COMMENT '消息发送时间',
  `agree` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否同意 0默认 1同意 2拒绝',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_report
-- ----------------------------
DROP TABLE IF EXISTS `im_report`;
CREATE TABLE `im_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_uid` int(11) NOT NULL COMMENT '举报人id',
  `report_user` varchar(155) NOT NULL COMMENT '举报人名',
  `reported_uid` int(11) NOT NULL COMMENT '被举报人id',
  `reported_user` varchar(155) NOT NULL COMMENT '被举报人名',
  `addtime` int(11) NOT NULL COMMENT '举报时间',
  `content` text COMMENT '举报证据',
  `report_type` varchar(155) NOT NULL COMMENT '举报类型大类',
  `report_detail` varchar(155) NOT NULL COMMENT '举报类型小类',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for im_user
-- ----------------------------
DROP TABLE IF EXISTS `im_user`;
CREATE TABLE `im_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(155) DEFAULT NULL,
  `pwd` varchar(155) DEFAULT NULL COMMENT '密码',
  `sign` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `sex` tinyint(1) DEFAULT '1' COMMENT '性别 1男 -1女',
  `age` int(3) DEFAULT '18' COMMENT '年龄',
  `pid` int(10) DEFAULT '110000' COMMENT '所在省份id',
  `cid` int(10) DEFAULT '110000' COMMENT '所在城市id',
  `aid` int(10) DEFAULT '110101' COMMENT '所在区id',
  `area` varchar(255) DEFAULT '北京-北京市-东城区' COMMENT '所在区域描述',
  `status` tinyint(1) DEFAULT '0' COMMENT '0下线 1在线',
  `fd` int(11) NOT NULL DEFAULT '-1' COMMENT '用户绑定的ft',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
