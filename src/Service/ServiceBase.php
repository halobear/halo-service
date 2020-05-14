<?php

namespace HaloService\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ServiceBase
{
    /**
     * @var Model $model
     */
    protected $model;

    /**
     * 列表
     *
     * @param        $param
     * @param  int  $page
     * @param  int  $per_page
     * @param  mixed  $field
     * @param  array  $order
     * @param  array  $select
     * @return array
     */
    public function list($param, $page = 1, $per_page = 20, $field = ['id'], $order = ['desc'], $select = ['*'])
    {
        $query   = $this->model->newQuery();
        $columns = Schema::getColumnListing($this->model->getTable());

        // 搜索字段
        foreach ($param as $key => $item) {
            if (!in_array($key, $columns)) {
                unset($param[$key]);
            }
        }

        if ($param) {
            foreach ($param as $key => $value) {
                if ($value !== '') {
                    if (in_array($key, ['name', 'phone', 'title'])) {
                        $query->where($key, 'like', '%'.$value.'%');
                    } else {
                        $query->where($key, $value);
                    }
                }
            }
        }

        // 总数
        $total = $query->count('id');
        $list  = [];
        if ($total) {
            $list = $query->select($select)->forPage($page, $per_page)->orderBy($field, $order)->get();
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 数据创建
     *
     * @param $param
     * @return \Illuminate\Database\Eloquent\Builder|Model
     */
    public function store($param)
    {
        method_exists($this, 'createValidation') && $this->createValidation($param);

        return $this->model->newQuery()->create($param);
    }

    /**
     * 详情
     *
     * @param $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function show($id)
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * 更新
     *
     * @param $id
     * @param $param
     * @return bool
     */
    public function update($id, $param)
    {
        method_exists($this, 'updateValidation') && $this->updateValidation($id, $param);

        $data = $this->model->newQuery()->findOrFail($id);

        return $data->fill($param)->save();
    }

    /**
     * 删除
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->model->newQuery()->where('id', $id)->delete();
    }

    /**
     * 禁用
     *
     * @param $id
     * @return int
     */
    public function forbid($id)
    {
        $ret = $this->model->newQuery()->where('id', $id)->update(['status' => 0]);

        return $ret;
    }

    /**
     * 启用
     *
     * @param $id
     * @return int
     */
    public function resume($id)
    {
        $ret = $this->model->newQuery()->where('id', $id)->update(['status' => 1]);

        return $ret;
    }

    /**
     * 基类查询信息
     *
     * @return array
     */
    public function filterList()
    {
        $page          = request()->get('page', 1);
        $per_page      = request()->get('per_page', 20);
        $order_field   = request()->get('order_field', 'id');
        $order_type    = request()->get('order_type', 'desc');
        $select        = request()->get('select', ['*']);
        $condition     = request()->get('condition', request()->all());
        $with          = request()->get('with');
        $has_condition = request()->get('has_condition');
        $where_in      = request()->get('where_in', []);
        $where_not_in  = request()->get('where_not_in', []);
        $diy_order     = request()->get('diy_order');
        $with          = $with ? json_decode($with, 1) : [];
        $has_condition = $has_condition ? json_decode($has_condition, 1) : [];

        $query = $this->model->newQuery();

        $columns = Schema::getColumnListing($this->model->getTable());
        foreach ($condition as $key => $item) {
            if (!in_array($key, $columns) || $condition[$key] === '') {
                unset($condition[$key]);
            }
        }
        $data = $condition;
        unset($condition['name']);
        unset($condition['title']);
        // Log::info('打印参数', $condition);
        $query->where($condition);

        if (isset($data['name']) && $data['name']) {
            $query->where(
                function ($query) use ($data) {
                    $query->where('name', 'like', '%'.$data['name'].'%');
                }
            );
        }
        if (isset($data['title']) && $data['title']) {
            $query->where(
                function ($query) use ($data) {
                    $query->where('title', 'like', '%'.$data['title'].'%');
                }
            );
        }
        foreach ($with as $info) {
            $query->with(
                [
                    $info['name'] => function ($query) use ($info) {
                        (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                        (isset($info['order_field']) && $info['order_field']) && $query->orderBy(
                            $info['order_field'],
                            $info['order_type'] ?? 'asc'
                        );
                        (isset($info['select']) && $info['select']) && $query->select($info['select']);
                    },
                ]
            );
        }

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                }
            );
        }

        foreach ($where_in as $info) {
            if (isset($info['list']) && $info['list']) {
                $query->whereIn($info['field'], $info['list']);
            }
        }
        foreach ($where_not_in as $info) {
            if (isset($info['list']) && $info['list']) {
                $query->whereNotIn($info['field'], $info['list']);
            }
        }

        // 总数
        $total = $query->count('id');
        if ($diy_order) {
            $query = $query->select($select)->forPage($page, $per_page);
            foreach ($diy_order as $item) {
                $query = $query->orderBy($item['order_field'], $item['order_type']);
            }
            $list = $query->get();
        } else {
            $list = $query->select($select)->forPage($page, $per_page)->orderBy($order_field, $order_type)->get();
        }

        return compact('total', 'list');
    }

    /**
     * 基类关联查询详情
     *
     * @param       $id
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function withShow($id)
    {
        $select        = request()->get('select', ['*']);
        $with          = request()->get('with');
        $has_condition = request()->get('has_condition');
        $with          = $with ? json_decode($with, 1) : [];
        $has_condition = $has_condition ? json_decode($has_condition, 1) : [];

        $query = $this->model->newQuery();
        foreach ($with as $info) {
            $query->with(
                [
                    $info['name'] => function ($query) use ($info) {
                        $info['condition'] && $query->where($info['condition']);
                        $info['order_field'] && $query->orderBy($info['order_field'], $info['order_type'] ?? 'asc');
                        $info['select'] && $query->select($info['select']);
                    },
                ]
            );
        }

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    $info['condition'] && $query->where($info['condition']);
                }
            );
        }

        return $query->where('id', $id)->select($select)->first();
    }
}
