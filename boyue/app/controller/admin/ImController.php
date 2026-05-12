<?php

namespace app\controller\admin;

use support\Request;
use support\Db;

class ImController
{
    public function groupList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $name = $request->get('name', '');
        $owner_name = $request->get('owner_name', '');
        $status = $request->get('status', '');

        $query = Db::table('im_groups as g')
            ->leftJoin('caipiao_member as m', 'g.owner_uid', '=', 'm.id')
            ->select('g.*', 'm.username as owner_name', 'm.face as owner_avatar');

        if ($name) {
            $query->where('g.name', 'like', "%{$name}%");
        }
        if ($owner_name) {
            $query->where('m.username', 'like', "%{$owner_name}%");
        }
        if ($status !== '') {
            $query->where('g.is_mute', $status == 0 ? 1 : 0);
        }

        $count = $query->count();
        $offset = ($page - 1) * $limit;

        $list = $query->orderBy('g.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['status'] = $row['is_mute'] ? 0 : 1;
            $row['status_text'] = $row['is_mute'] ? '禁言' : '正常';
            $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            $row['updated_at'] = date('Y-m-d H:i:s', $row['updated_at']);
            $result[] = $row;
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $result, 'count' => $count]);
    }

    public function groupDetail(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $group = Db::table('im_groups')->where('id', $id)->first();
        if (!$group) {
            return json(['code' => 1, 'msg' => '群聊不存在']);
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => (array)$group]);
    }

    public function groupCreate(Request $request)
    {
        $name = $request->post('name');
        $avatar = $request->post('avatar', '');
        $notice = $request->post('notice', '');
        $max_members = $request->post('max_members', 200);

        if (!$name) {
            return json(['code' => 1, 'msg' => '群名称不能为空']);
        }

        $now = time();
        $id = Db::table('im_groups')->insertGetId([
            'name' => $name,
            'avatar' => $avatar,
            'owner_uid' => 0,
            'member_count' => 0,
            'max_members' => $max_members,
            'notice' => $notice,
            'is_mute' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return json(['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $id]]);
    }

    public function groupUpdate(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $data = ['updated_at' => time()];

        $name = $request->post('name');
        if ($name !== null) $data['name'] = $name;

        $avatar = $request->post('avatar');
        if ($avatar !== null) $data['avatar'] = $avatar;

        $notice = $request->post('notice');
        if ($notice !== null) $data['notice'] = $notice;

        $max_members = $request->post('max_members');
        if ($max_members !== null) $data['max_members'] = $max_members;

        Db::table('im_groups')->where('id', $id)->update($data);

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    public function groupDelete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_group_members')->where('group_id', $id)->delete();
        Db::table('im_messages')->where('to_group', $id)->delete();
        Db::table('im_groups')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function groupToggleStatus(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $group = Db::table('im_groups')->where('id', $id)->first();
        if (!$group) {
            return json(['code' => 1, 'msg' => '群聊不存在']);
        }

        Db::table('im_groups')->where('id', $id)->update([
            'is_mute' => $group->is_mute ? 0 : 1,
            'updated_at' => time()
        ]);

        return json(['code' => 0, 'msg' => '状态已更新']);
    }

    public function groupMembers(Request $request)
    {
        $group_id = $request->get('group_id');
        if (!$group_id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $list = Db::table('im_group_members as gm')
            ->leftJoin('caipiao_member as m', 'gm.uid', '=', 'm.id')
            ->where('gm.group_id', $group_id)
            ->select('gm.*', 'm.username', 'm.face as avatar')
            ->orderByRaw('FIELD(gm.role, 2, 1, 0) DESC')
            ->orderBy('gm.joined_at', 'asc')
            ->get();

        $roleMap = [0 => '成员', 1 => '管理员', 2 => '群主'];
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['user_id'] = $row['uid'];
            $row['role_text'] = $roleMap[$row['role']] ?? '成员';
            $row['role'] = $row['role'] == 2 ? 'owner' : ($row['role'] == 1 ? 'admin' : 'member');
            $row['joined_at'] = date('Y-m-d H:i:s', $row['joined_at']);
            $result[] = $row;
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['list' => $result]]);
    }

    public function groupRemoveMember(Request $request)
    {
        $group_id = $request->post('group_id');
        $user_id = $request->post('user_id');

        if (!$group_id || !$user_id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_group_members')
            ->where('group_id', $group_id)
            ->where('uid', $user_id)
            ->delete();

        Db::table('im_groups')->where('id', $group_id)->decrement('member_count');

        return json(['code' => 0, 'msg' => '移除成功']);
    }

    public function groupOptions(Request $request)
    {
        $list = Db::table('im_groups')
            ->select('id as value', 'name as label')
            ->orderBy('id', 'desc')
            ->get();

        return json(['code' => 0, 'msg' => 'ok', 'data' => $list]);
    }

    public function groupMessageList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $group_id = $request->get('group_id', '');
        $sender_name = $request->get('sender_name', '');
        $message_type = $request->get('message_type', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');

        $query = Db::table('im_messages as msg')
            ->leftJoin('caipiao_member as m', 'msg.from_uid', '=', 'm.id')
            ->leftJoin('im_groups as g', 'msg.to_group', '=', 'g.id')
            ->where('msg.to_group', '>', 0)
            ->select('msg.*', 'm.username as sender_name', 'm.face as sender_avatar', 'g.name as group_name');

        if ($group_id) {
            $query->where('msg.to_group', $group_id);
        }
        if ($sender_name) {
            $query->where('m.username', 'like', "%{$sender_name}%");
        }
        if ($message_type) {
            $typeMap = ['text' => 1, 'image' => 2, 'voice' => 3, 'video' => 4, 'file' => 5];
            $query->where('msg.msg_type', $typeMap[$message_type] ?? 1);
        }
        if ($sDate) {
            $query->where('msg.created_at', '>=', strtotime($sDate));
        }
        if ($eDate) {
            $query->where('msg.created_at', '<=', strtotime($eDate . ' 23:59:59'));
        }

        $count = $query->count();
        $offset = ($page - 1) * $limit;

        $list = $query->orderBy('msg.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeTextMap = [1 => '文字', 2 => '图片', 3 => '语音', 4 => '视频', 5 => '文件'];
        $typeNameMap = [1 => 'text', 2 => 'image', 3 => 'voice', 4 => 'video', 5 => 'file'];
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['message_type'] = $typeNameMap[$row['msg_type']] ?? 'text';
            $row['message_type_text'] = $typeTextMap[$row['msg_type']] ?? '文字';
            $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            $result[] = $row;
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $result, 'count' => $count]);
    }

    public function groupMessageDelete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_messages')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function groupMessageBatchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_messages')->whereIn('id', $ids)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function userMessageList(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $from_username = $request->get('from_username', '');
        $to_username = $request->get('to_username', '');
        $message_type = $request->get('message_type', '');
        $sDate = $request->get('sDate', '');
        $eDate = $request->get('eDate', '');

        $query = Db::table('im_messages as msg')
            ->leftJoin('caipiao_member as from_m', 'msg.from_uid', '=', 'from_m.id')
            ->leftJoin('caipiao_member as to_m', 'msg.to_uid', '=', 'to_m.id')
            ->where('msg.to_uid', '>', 0)
            ->where('msg.to_group', 0)
            ->select(
                'msg.*',
                'from_m.username as from_username',
                'from_m.face as from_avatar',
                'to_m.username as to_username',
                'to_m.face as to_avatar'
            );

        if ($from_username) {
            $query->where('from_m.username', 'like', "%{$from_username}%");
        }
        if ($to_username) {
            $query->where('to_m.username', 'like', "%{$to_username}%");
        }
        if ($message_type) {
            $typeMap = ['text' => 1, 'image' => 2, 'voice' => 3, 'video' => 4, 'file' => 5];
            $query->where('msg.msg_type', $typeMap[$message_type] ?? 1);
        }
        if ($sDate) {
            $query->where('msg.created_at', '>=', strtotime($sDate));
        }
        if ($eDate) {
            $query->where('msg.created_at', '<=', strtotime($eDate . ' 23:59:59'));
        }

        $count = $query->count();
        $offset = ($page - 1) * $limit;

        $list = $query->orderBy('msg.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $typeTextMap = [1 => '文字', 2 => '图片', 3 => '语音', 4 => '视频', 5 => '文件'];
        $typeNameMap = [1 => 'text', 2 => 'image', 3 => 'voice', 4 => 'video', 5 => 'file'];
        $result = [];
        foreach ($list as $item) {
            $row = (array)$item;
            $row['message_type'] = $typeNameMap[$row['msg_type']] ?? 'text';
            $row['message_type_text'] = $typeTextMap[$row['msg_type']] ?? '文字';
            $row['created_at'] = date('Y-m-d H:i:s', $row['created_at']);
            $result[] = $row;
        }

        return json(['code' => 0, 'msg' => 'ok', 'data' => $result, 'count' => $count]);
    }

    public function userMessageDelete(Request $request)
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_messages')->where('id', $id)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }

    public function userMessageBatchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        Db::table('im_messages')->whereIn('id', $ids)->delete();

        return json(['code' => 0, 'msg' => '删除成功']);
    }
}
