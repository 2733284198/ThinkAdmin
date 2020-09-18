<?php

namespace app\data\service;

use think\admin\Service;

/**
 * 文章数据处理服务
 * Class NewsService
 * @package app\data\service
 */
class NewsService extends Service
{
    /**
     * 同步文章数据统计
     * @param integer $cid 文章记录ID
     * @throws \think\db\exception\DbException
     */
    public function syncNewsTotal(int $cid)
    {
        [$map, $total] = [['cid' => $cid], []];
        $query = $this->app->db->name('DataNewsXCollect')->field('count(1) count,type');
        $query->where($map)->group('type')->select()->map(function ($item) use (&$total) {
            $total[$item['type']] = $item['count'];
        });
        $this->app->db->name('DataNewsItem')->where(['id' => $cid])->update([
            'num_collect' => $total[2] ?? 0, 'num_like' => $total[1] ?? 0,
            'num_comment' => $this->app->db->name('DataNewsXComment')->where($map)->count(),
        ]);
    }

    /**
     * 根据CID绑定列表数据
     * @param array $list 数据列表
     * @return array
     */
    public function buildListByCid(array &$list = []): array
    {
        if (count($list) > 0) {
            $cids = array_unique(array_column($list, 'cid'));
            $cols = 'id,name,cover,status,deleted,create_at,num_like,num_read,num_comment,num_collect';
            $news = $this->app->db->name('DataNewsItem')->whereIn('id', $cids)->column($cols, 'id');
            foreach ($list as &$vo) $vo['record'] = $news[$vo['cid']] ?? [];
        }
        return $list;
    }

    /**
     * 根据MID绑定列表数据
     * @param array $list 数据列表
     * @return array
     */
    public function buildListByMid(array &$list = []): array
    {
        if (count($list) > 0) {
            $mids = array_unique(array_column($list, 'mid'));
            $cols = 'id,phone,nickname,username,headimg,status';
            $mems = $this->app->db->name('DataMember')->whereIn('id', $mids)->column($cols, 'id');
            foreach ($list as &$vo) $vo['member'] = $mems[$vo['mid']] ?? [];
        }
        return $list;
    }

    /**
     * 获取列表状态
     * @param array $list 数据列表
     * @param integer $mid 会员MID
     * @return array
     */
    public function buildListState(array &$list, int $mid = 0): array
    {
        if (count($list) > 0 && $mid > 0) {
            $map = [['mid', '=', $mid], ['cid', 'in', array_column($list, 'id')]];
            $cid1s = $this->app->db->name('DataNewsXCollect')->where($map)->where(['type' => 2])->column('cid');
            $cid2s = $this->app->db->name('DataNewsXCollect')->where($map)->where(['type' => 1])->column('cid');
            foreach ($list as &$vo) {
                $vo['my_like_state'] = in_array($vo['id'], $cid1s) ? 1 : 0;
                $vo['my_coll_state'] = in_array($vo['id'], $cid2s) ? 1 : 0;
            }
        }
        return $list;
    }

}