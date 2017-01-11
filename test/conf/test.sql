create table if not exists `url` (
`id` int unsigned not null auto_increment primary key,
`a_id` varchar(20) not null default '' unique key comment 'a href id',
`p_id` int unsigned not null default 0 key comment 'parent id',
`ch_name` varchar(20) not null default '' comment 'chinese name',
`en_name` varchar(20) not null default '' comment 'english name',
`m` varchar(20) not null default '' comment 'module',
`c` varchar(20) not null default '' comment 'controller',
`a` varchar(20) not null default '' comment 'action',
unique key `m_c_a` (`m`, `c`, `a`)
);