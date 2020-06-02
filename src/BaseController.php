<?php

namespace HaloService;

use HaloService\BaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var ServiceBase $service
     */
    protected $service;

    /**
     * 列表
     *
     * @return Response
     */
    public function index()
    {
        $request_body = data_get(request()->get('request_body'), 'request_body', '[]');
        $request_body = json_decode($request_body, 1);

        return success($this->service->index($request_body));
    }

    /**
     * 数据创建
     *
     * @param $param
     * @return Response
     */
    public function store()
    {
        return success($this->service->store(request()->all()));
    }

    /**
     * 详情
     *
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        return success($this->service->show($id));
    }

    /**
     * 更新
     *
     * @param $id
     * @return Response
     */
    public function update($id)
    {
        $ret = $this->service->update($id, request()->all());

        return success($ret);
    }

    /**
     * 删除
     *
     * @param $id
     * @return Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $ret = $this->service->destroy($id);

        return success($ret, '删除成功');
    }

    /**
     * 禁用
     *
     * @param $id
     * @return Response
     */
    public function forbid($id)
    {
        $ret = $this->service->forbid($id);

        return success($ret, '禁用成功');
    }

    /**
     * 启用
     *
     * @param $id
     * @return Response
     */
    public function resume($id)
    {
        $ret = $this->service->resume($id);

        return success($ret, '启用成功');
    }

    /**
     * 表单验证
     * @param $rule
     */
    public function validation($rule)
    {
        $validator = Validator::make(request()->all(), $rule, $this->message());
        abort_if($validator->fails(), 422, $validator->errors()->first());
    }

    public function validationParam($param, $rule)
    {
        $validator = Validator::make($param, $rule, $this->message());
        abort_if($validator->fails(), 422, 'json对象下的' . $validator->errors()->first());
    }

    private function message()
    {
        return [
            'accepted'   => ':attribute 是被接受的',
            'active_url' => ':attribute 必须是一个合法的 URL',
            'after'      => ':attribute 必须是 :date 之后的一个日期',
            'alpha'      => ':attribute 必须全部由字母字符构成。',
            'alpha_dash' => ':attribute 必须全部由字母、数字、中划线或下划线字符构成',
            'alpha_num'  => ':attribute 必须全部由字母和数字构成',
            'array'      => ':attribute 必须是个数组',
            'before'     => ':attribute 必须是 :date 之前的一个日期',
            'between'    => ':attribute 必须在 :min 到 :max 之间',

            'boolean'        => ':attribute 字符必须是 true 或 false',
            'confirmed'      => ':attribute 二次确认不匹配',
            'date'           => ':attribute 必须是一个合法的日期',
            'date_format'    => ':attribute 与给定的格式 :format 不符合',
            'different'      => ':attribute 必须不同于:other',
            'digits'         => ':attribute 必须是 :digits 位',
            'digits_between' => ':attribute 必须在 :min and :max 位之间',
            'dimensions'     => ':attribute 图像尺寸不合法',
            'distinct'       => ':attribute 字段值不能重复.',
            'email'          => ':attribute 必须是一个合法的电子邮件地址。',
            'exists'         => '选定的 :attribute 是无效的',
            'file'           => ':attribute 必须是文件',
            'filled'         => ':attribute 的字段是必填的',
            'image'          => ':attribute 必须是一个图片 (jpeg, png, bmp 或者 gif)',
            'in'             => '选定的 :attribute 是无效的',
            'in_array'       => ':attribute 不在 :other 中',
            'integer'        => ':attribute 必须是个整数',
            'ip'             => ':attribute 必须是一个合法的 IP 地址。',
            'json'           => ':attribute 必须是一个合法的 JSON 字符串',
            'max'            => ':attribute 最大不能超过 :max',
            'mimes'          => ':attribute 的文件类型必须是:values',
            'mimetypes'      => ':attribute 的文件类型必须是:values',
            'min'            => ':attribute 最小不能小于 :min',

            'not_in'               => '选定的 :attribute 是无效的',
            'numeric'              => ':attribute 必须是数字',
            'present'              => ':attribute 字段必须存在',
            'regex'                => ':attribute 格式是无效的',
            'required'             => ':attribute 字段必须填写',
            'required_if'          => ':attribute 字段是必须的当 :other 是 :value',
            'required_unless'      => ':attribute 字段是必须的除非 :other 在 :values 中',
            'required_with'        => ':attribute 字段是必须的当 :values 是存在的',
            'required_with_all'    => ':attribute 字段是必须的当 :values 是存在的',
            'required_without'     => ':attribute 字段是必须的当 :values 是不存在的',
            'required_without_all' => ':attribute 字段是必须的当 没有一个 :values 是存在的',
            'same'                 => ':attribute 和 :other 必须匹配',
            'size'                 => ':attribute 必须是 :size 位',
            'string'               => ':attribute 必须是字符串',
            'timezone'             => ':attribute 必须个有效的时区',
            'unique'               => ':attribute 已存在',
            'uploaded'             => ':attribute 上传失败',
            'url'                  => ':attribute 无效的格式',
            'phone'                => ':attribute 无效的手机号格式',
            'artisan_name'         => ':attribute 字符长度最大24位',
        ];
    }

    /**
     * 获得请求参参数 把为null的重写为空字符串
     * @param  array  $keys
     * @return array
     */
    protected function getReqParams($keys = [])
    {
        $params = request()->all();
        $ret    = [];

        if (!empty($keys)) {
            foreach ($keys as $k => $v) {
                if (is_numeric($k)) { // 一维数组
                    $ret[$v] = array_key_exists($v, $params) ? ($params[$v] == '' ? '' : trim($params[$v])) : '';
                    continue;
                }
                $ret[$k] = array_key_exists($k, $params) ? ($params[$k] == '' ? '' : trim($params[$k])) : ($v == '' ? '' : $v);
            }
        } else {
            $ret = $params;
        }

        return $ret;
    }
}
