<?php

namespace HaloService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BaseService
{
    /**
     * @var Model $model
     */
    protected $model;

    /**
     * 列表页
     *
     * @return array
     */
    public function index($request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }
        $page             = data_get($request_body, 'page', 1);
        $per_page         = data_get($request_body, 'per_page', 1000000);
        $select           = data_get($request_body, 'select', ['*']);
        $select_raw       = data_get($request_body, 'select_raw', []);
        $order_raw        = data_get($request_body, 'order_raw', []);
        $condition        = data_get($request_body, 'condition', request()->except(['request_body']) ?: []);
        $max_sort         = data_get($request_body, 'max_sort', 0);
        $where_in         = data_get($request_body, 'where_in', []);
        $where_not_in     = data_get($request_body, 'where_not_in', []);
        $order            = data_get($request_body, 'order', []);
        $with             = data_get($request_body, 'with', []);
        $with_count       = data_get($request_body, 'with_count', []);
        $has_condition    = data_get($request_body, 'has_condition', []);
        $has_or_condition = data_get($request_body, 'has_or_condition', []);
        $order_by_raw     = data_get($request_body, 'order_by_raw', []);
        $where_raw        = data_get($request_body, 'where_raw', []);

        $query            = $this->model->newQuery();

        $columns = Schema::getColumnListing($this->model->getTable());
        foreach ($condition as $key => $item) {
            if (is_numeric($key)) {
                // 过滤字段在数组中,
                if (!in_array(data_get($item, '0'), $columns) || data_get($item, '0') === '') {
                    unset($condition[$key]);
                }
            } else {
                // 过滤字段在键名中
                if (!in_array($key, $columns) || $condition[$key] === '') {
                    unset($condition[$key]);
                }
            }
        }


        $query->where($condition);

        foreach ($with as $info) {
            $with_item = [
                $info['name'] => function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    // orderByRaw
                    if(isset($info['order_by_raw'])){
                        $with_order_by_raw = $info['order_by_raw'];
                        if ($with_order_by_raw && isset($with_order_by_raw['sql']) && $with_order_by_raw['sql']) {
                            $query->orderByRaw($with_order_by_raw['sql'], $with_order_by_raw['bindings'] ?? []);
                        }
                    }
                    (isset($info['order_field']) && $info['order_field']) && $query->orderBy(
                        $info['order_field'],
                        $info['order_type'] ?? 'asc'
                    );
                    (isset($info['select']) && $info['select']) && $query->select($info['select']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
                },
            ];
            $query->with($with_item);
            // $query->withCount($with_item);
        }
        $query = $query->select($select);

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }



                    if (isset($info['or_condition']) && $info['or_condition'] &&  is_array($info['or_condition'])) {
                        $query->where(function ($q) use ($info){
                            foreach ($info['or_condition'] as $or_c) {
                                if (isset($or_c['name']) && isset($or_c['operator']) && isset($or_c['value'])) {
                                    $q->orWhere($or_c['name'], $or_c['operator'], $or_c['value']);
                                }
                            }
                        });

                    }

                }
            );
        }

        // 多查询条件模糊查询
        if ($has_or_condition && is_array($has_or_condition)) {
            foreach ($has_or_condition as $or_condition) {
                if ($or_condition) {
                    $query->where(
                        function ($query) use ($or_condition) {
                            if (is_array($or_condition)) {
                                foreach ($or_condition as $or_c) {
                                    if (isset($or_c['name']) && isset($or_c['operator']) && isset($or_c['value'])) {
                                        $query->orWhere($or_c['name'], $or_c['operator'], $or_c['value']);
                                    } elseif (array_values($or_c) === $or_c) {
                                        $query->orWhere(
                                            function ($query) use ($or_c) {
                                                foreach ($or_c as $c) {
                                                    if (isset($c['name'])) {
                                                        $query->where(
                                                            $c['name'],
                                                            $c['operator'] ?? null,
                                                            $c['value'] ?? null,
                                                            $c['boolean'] ?? 'and'
                                                        );
                                                    }
                                                }
                                            }
                                        );
                                    }
                                }
                            }
                        }
                    );
                }
            }
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

        // whereRaw
        foreach ($where_raw as $item) {
            if ($item && isset($item['sql']) && $item['sql']) {
                $query->whereRaw($item['sql'], $item['bindings'] ?? [], $item['boolean'] ?? 'and');
            }
        }

        // 总数
        $key = $this->model->getKeyName();
        // 总数
        $total = $query->count($key);
        $query->select($select);
        foreach ($select_raw as $item) {
            if (is_array($item) && isset($item['sql']) && isset($item['bindings'])) {
                $query->selectRaw($item['sql'], $item['bindings']);
            } elseif (is_string($item)) {
                $query->selectRaw($item);
            }
        }

        foreach ($with_count as $info) {
            if (isset($info['condition']) && $info['condition']) {
                $query->withCount(
                    [
                        $info['name'] => function ($query) use ($info) {
                            $query->where($info['condition']);
                        },
                    ]
                );
            } else {
                $query->withCount($info['name']);
            }
        }

        if($order_raw){
            foreach ($order_raw as $item) {
                $query->orderByRaw($item);
            }
        }

        foreach ($order as $item) {
            $query = $query->orderBy($item['order_field'], $item['order_type']);
        }

        // orderByRaw
        if ($order_by_raw && isset($order_by_raw['sql']) && $order_by_raw['sql']) {
            $query->orderByRaw($order_by_raw['sql'], $order_by_raw['bindings'] ?? []);
        }

        $list = $query->forPage($page, $per_page)->get();

        if ($max_sort) {
            $maxSort  = $this->model->newQuery()->select('sort')->orderBy('sort', 'desc')->value('sort');
            $max_sort = $maxSort ?: 0;

            return compact('max_sort', 'total', 'list');
        }

        return compact('total', 'list');
    }

    // 添加（可批量添加）
    public function store($request_body)
    {
        if ($request_body && is_array($request_body) && array_values($request_body) === $request_body) {
            return $this->model->newQuery()->insert($request_body);
        }
        return $this->model->newQuery()->create($request_body);
    }

    /**
     * 详情
     *
     * @param $id
     * @param $request_body
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function show($id, $request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }

        if ($request_body) {
            $select        = data_get($request_body, 'select', ['*']);
            $with          = data_get($request_body, 'with', []);
            $has_condition = data_get($request_body, 'has_condition', []);
            $select_raw    = data_get($request_body, 'select_raw', []);
            $with_count    = data_get($request_body, 'with_count', []);
            $query         = $this->model->newQuery();
            foreach ($with as $info) {
                $with_item = [
                    $info['name'] => function ($query) use ($info) {
                        (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                        // orderByRaw
                        if(isset($info['order_by_raw'])){
                            $with_order_by_raw = $info['order_by_raw'];
                            if ($with_order_by_raw && isset($with_order_by_raw['sql']) && $with_order_by_raw['sql']) {
                                $query->orderByRaw($with_order_by_raw['sql'], $with_order_by_raw['bindings'] ?? []);
                            }
                        }
                        (isset($info['order_field']) && $info['order_field']) && $query->orderBy(
                            $info['order_field'],
                            $info['order_type'] ?? 'asc'
                        );
                        (isset($info['select']) && $info['select']) && $query->select($info['select']);
                        if (isset($info['where_in']) && $info['where_in']) {
                            foreach ($info['where_in'] as $item) {
                                $query->whereIn($item['field'], $item['list']);
                            }
                        }
                    },
                ];
                $query->with($with_item);
                // $query->withCount($with_item);
            }

            $query->select($select);
            foreach ($select_raw as $item) {
                $query->selectRaw($item);
            }

            foreach ($with_count as $info) {
                if (isset($info['condition']) && $info['condition']) {
                    $query->withCount(
                        [
                            $info['name'] => function ($query) use ($info) {
                                $query->where($info['condition']);
                            },
                        ]
                    );
                } else {
                    $query->withCount($info['name']);
                }
            }

            foreach ($has_condition as $info) {
                $query->whereHas(
                    $info['name'],
                    function ($query) use ($info) {
                        (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                        if (isset($info['where_in']) && $info['where_in']) {
                            foreach ($info['where_in'] as $item) {
                                $query->whereIn($item['field'], $item['list']);
                            }
                        }
                    }
                );
            }

            return $query->where('id', $id)->first();
        } else {
            return $this->model->newQuery()->find($id);
        }
    }

    /**
     * 通过条件查询详情
     *
     * @param $request_body
     * @return \Illuminate\Database\Eloquent\Builder|Model
     */
    public function showByCondition($request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }

        $select        = data_get($request_body, 'select', ['*']);
        $with          = data_get($request_body, 'with', []);
        $has_condition = data_get($request_body, 'has_condition', []);
        $condition     = data_get($request_body, 'condition', []);
        $query         = $this->model->newQuery()->where($condition);
        foreach ($with as $info) {
            $with_item = [
                $info['name'] => function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    (isset($info['order_field']) && $info['order_field']) && $query->orderBy(
                        $info['order_field'],
                        $info['order_type'] ?? 'asc'
                    );
                    (isset($info['select']) && $info['select']) && $query->select($info['select']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
                },
            ];
            $query->with($with_item);
            // $query->withCount($with_item);
        }

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
                }
            );
        }

        return $query->where($has_condition)->select($select)->first();
    }


    /**
     * 更新
     *
     * @param $id
     * @param $request_body
     * @return bool
     */
    public function update($id, $request_body)
    {
        $data = $this->model->newQuery()->find($id);

        return $data->fill($request_body)->save();
    }

    /**
     * 根据条件更新
     *
     * @param $param
     * @return bool
     */
    public function updateByCondition($param, $request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }

        $condition     = data_get($request_body, 'condition', []);
        $has_condition = data_get($request_body, 'has_condition', []);
        $where_in      = data_get($request_body, 'where_in', []);
        $where_not_in  = data_get($request_body, 'where_not_in', []);

        $columns = Schema::getColumnListing($this->model->getTable());
        $query   = $this->model->newQuery()->where($condition);

        foreach ($param as $key => $item) {
            if (!in_array($key, $columns)) {
                unset($param[$key]);
            }
        }

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
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

        return $query->update($param);
    }

    /**
     * 删除
     *
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function destroy($id)
    {
        $model = $this->model->newQuery()->where('id', $id)->first();
        if ($model) {
            $model->delete();
        }

        return compact('id');
    }

    /**
     * 通过条件进行删除
     *
     * @return mixed
     */
    public function destroyByCondition($request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }
        $condition     = data_get($request_body, 'condition', []);
        $has_condition = data_get($request_body, 'has_condition', []);
        $where_in      = data_get($request_body, 'where_in', []);
        $where_not_in  = data_get($request_body, 'where_not_in', []);
        $query         = $this->model->newQuery()->where($condition);
        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
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

        return $query->delete();
    }

    /**
     * 禁用
     *
     * @param $id
     * @return int
     */
    public function forbid($id)
    {
        $ret = $this->model->newQuery()->find($id);
        if (!$ret) {
            return 0;
        }
        $ret->update(['status' => 0]);

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
        $ret = $this->model->newQuery()->find($id);
        if (!$ret) {
            return 0;
        }
        $ret->update(['status' => 1]);

        return $ret;
    }

    /**
     * 得到统计数量
     *
     * @return int
     */
    public function getNum($request_body = [])
    {
        if (empty($request_body)) {
            $request_body = request()->get('request_body', '[]');
            $request_body = json_decode($request_body, 1);
        }
        $condition     = data_get($request_body, 'condition', []);
        $has_condition = data_get($request_body, 'has_condition', []);
        $where_in      = data_get($request_body, 'where_in', []);
        $where_not_in  = data_get($request_body, 'where_not_in', []);
        $query         = $this->model->newQuery();

        $columns = Schema::getColumnListing($this->model->getTable());
        foreach ($condition as $key => $item) {
            if (!in_array($key, $columns) || $condition[$key] === '') {
                unset($condition[$key]);
            }
        }

        $query->where($condition);

        foreach ($has_condition as $info) {
            $query->whereHas(
                $info['name'],
                function ($query) use ($info) {
                    (isset($info['condition']) && $info['condition']) && $query->where($info['condition']);
                    if (isset($info['where_in']) && $info['where_in']) {
                        foreach ($info['where_in'] as $item) {
                            $query->whereIn($item['field'], $item['list']);
                        }
                    }
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

        return $query->count('id');
    }

    // 批量保存，可添加、修改、删除
    public function batchStore($request_body)
    {
        $condition = data_get($request_body, 'condition', []);
        $data      = data_get($request_body, 'data', []);
        if (!$condition) {
            return [];
        }
        $oldIdArr = $this->model->newQuery()
            ->where($condition)
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();
        $resArr   = [];
        foreach ($data as $datum) {
            if (isset($datum['id']) && $datum['id']) {
                $model = $this->model->newQuery()->where('id', $datum['id'])->first();
                try {
                    $model->update($datum);
                    $resArr[] = $model;
                } catch (\Throwable $e) {
                    Log::info($e->getMessage());
                }
            } else {
                $model    = $this->model->newQuery()->create($datum);
                $resArr[] = $model;
            }
        }
        $idArr = collect($resArr)->pluck('id')->unique()->toArray();
        $this->model->newQuery()->whereIn('id', array_diff($oldIdArr, $idArr))->delete();
        return $resArr;
    }

    // 原生sql 可自定义表前缀标识
    public function raw($request_body)
    {
        $connection      = DB::connection();
        $tablePrefix     = $connection->getTablePrefix();
        $query           = data_get($request_body, 'query', '');
        $bindings        = data_get($request_body, 'bindings', []);
        $useReadPdo      = data_get($request_body, 'useReadPdo', true);
        $tablePrefixFlag = data_get($request_body, 'tablePrefixFlag', 'wtw_');
        $method          = data_get($request_body, 'method', 'select');
        $query           = str_replace($tablePrefixFlag, $tablePrefix, $query);
        if ($method === 'update') {
            return $connection->update($query, $bindings);
        } elseif ($method === 'insert') {
            return $connection->insert($query, $bindings);
        } elseif ($method === 'delete') {
            return $connection->delete($query, $bindings);
        }
        return $connection->select($query, $bindings, $useReadPdo);
    }

    /**
     * 自增
     *
     * @param $id
     * @param $request_body
     * @return bool
     */
    public function increment($id, $request_body)
    {
        $field = data_get($request_body, 'field');
        $num   = data_get($request_body, 'num');
        if ($field && $num) {
            $this->model->newQuery()->where('id', $id)->increment($field, $num);
        }

        return true;
    }
}
