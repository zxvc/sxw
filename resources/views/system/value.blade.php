@extends('layouts.include_withoutaui')
<head>
    <style>
        .switch-btn {
            cursor: pointer;
            width: 45px;
            height: 28px;
            position: relative;
            border: 1px solid #dfdfdf;
            background-color: #fdfdfd;
            box-shadow: #dfdfdf 0 0 0 0 inset;
            border-radius: 15px;
            background-clip: content-box;
            display: inline-block;
            -webkit-appearance: none;
            user-select: none;
            outline: none;
        }

        .switch-btn:before {
            content: '';
            width: 25px;
            height: 25px;
            position: absolute;
            top: 0;
            left: 0;
            border-radius: 20px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .4);
        }

        .switch-btn:checked {
            border-color: #56b0d4;
            box-shadow: #56b0d4 0 0 0 16px inset;
            background-color: #56b0d4;
        }

        .switch-btn:checked:before {
            left: 18px;
        }

        .switch-btn.switch-btn-animbg {
            transition: background-color ease .4s;
        }

        .switch-btn.switch-btn-animbg:before {
            transition: left .3s;
        }

        .switch-btn.switch-btn-animbg:checked {
            box-shadow: #dfdfdf 0 0 0 0 inset;
            background-color: #56b0d4;
            transition: border-color .4s, background-color ease .4s;
        }

        .switch-btn.switch-btn-animbg:checked:before {
            transition: left .3s;
        }
    </style>
</head>
@section('content')
    <div class="page-container">
        <form>
            <div class="text-c">
                <a class="btn btn-success radius r btn-refresh"
                   style="line-height:1.6em;margin-top:3px"
                   href="javascript:location.replace(location.href);" title="刷新"><i
                            class="Hui-iconfont">&#xe68f;</i></a>
            </div>
        </form>
        {{--<div class="mt-20"></div>--}}
        <div class="cl pd-5 bg-1 bk-gray mt-20">

            <span class="r">共有数据：<strong>{{$datas->count()}}</strong> 条</span></div>

        <table class="table table-border table-bordered table-bg table-sort">
            <thead>
            <tr class="text-c">
                {{--<th width="25"><input type="checkbox" name="" value=""></th>--}}
                <th width="60">id</th>
                <th width="300">名称</th>
                <th width="300">当前值</th>
                <th width="120">操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach($datas as $index=>$data)
                <tr class="text-c">
                    {{--<td><input type="checkbox" value="" name=""></td>--}}
                    <td>{{$data->id}}</td>
                    <td>{{$data->name}}</td>
                    <td>
                        @if($data->type==0)
                            {{$data->value}}
                        @elseif($data->type==1)
                            {{$data->value==1?"开启":"关闭"}}
                        @elseif($data->type==2)
                            <ul id="Huifold1" class="Huifold">
                                <li class="item">
                                    <h4>点击展开/关闭<b>+</b></h4>
                                    <div class="info"> {!! $data->value !!} </div>
                                </li>
                            </ul>
                        @endif
                    </td>
                    <td>@if($data->type==0)
                            <button class="btn radius btn-primary size-L"
                                    onClick='edit_value(0,"{{$index}}")'>编辑
                            </button>
                        @elseif($data->type==1)
                            <input name="value" id='{{$data->id}}' class="switch-btn switch-btn-animbg" type="checkbox"
                                   @if($data->value==1) checked @endif/>
                        @elseif($data->type==2)
                            <button class="btn radius btn-primary size-L"
                                    onClick='edit_value(2,"{{$index}}")'>编辑
                            </button>
                        @endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{--</div>--}}
    </div>
    <div id="edit_value" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content radius">
                <div class="modal-header">
                    <h3 class="modal-title" id="value-name"></h3>
                    <a class="close" data-dismiss="modal" aria-hidden="true" href="javascript:void();">×</a>
                </div>
                <form id="value-form">
                    <input id="value-id" name="id" value="0" class="hidden">
                    <div class="modal-body" id="value-value">
                    </div>
                    <div class="modal-footer">
                        <div class="btn btn-primary submit">确定</div>
                        <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="{{ URL::asset('hui/lib/My97DatePicker/4.8/WdatePicker.js')}}"></script>
    <script type="text/javascript" src="{{ URL::asset('hui/lib/datatables/1.10.0/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{ URL::asset('hui/lib/laypage/1.2/laypage.js')}}"></script>
    <script type="text/javascript" src="{{ URL::asset('hui/lib/My97DatePicker/4.8/WdatePicker.js')}}"></script>
    <script type="text/javascript" src="{{ URL::asset('hui/lib/datatables/1.10.0/jquery.dataTables.min.js')}}"></script>
    <script type="text/javascript" src="{{ URL::asset('hui/lib/laypage/1.2/laypage.js')}}"></script>
    <script type="text/javascript">
        var datas =@json($datas);
        $.Huifold = function (obj, obj_c, speed, obj_type, Event) {
            if (obj_type == 2) {
                $(obj + ":first").find("b").html("-");
                $(obj_c + ":first").show()
            }
            $(obj).bind(Event, function () {
                if ($(this).next().is(":visible")) {
                    if (obj_type == 2) {
                        return false
                    }
                    else {
                        $(this).next().slideUp(speed).end().removeClass("selected");
                        $(this).find("b").html("+")
                    }
                }
                else {
                    if (obj_type == 3) {
                        $(this).next().slideDown(speed).end().addClass("selected");
                        $(this).find("b").html("-")
                    } else {
                        $(obj_c).slideUp(speed);
                        $(obj).removeClass("selected");
                        $(obj).find("b").html("+");
                        $(this).next().slideDown(speed).end().addClass("selected");
                        $(this).find("b").html("-")
                    }
                }
            })
        }

        $(function () {
            $.Huifold("#Huifold1 .item h4", "#Huifold1 .item .info", "fast", 1, "click");
            /*5个参数顺序不可打乱，分别是：相应区,隐藏显示的内容,速度,类型,事件*/
        });

        function edit_value(type, index) {
            console.log(datas);
            var id, name, value;
            id = datas[index].id;
            name = datas[index].name;
            value = datas[index].value;
            $("#value-name").html(name);
            $("#value-id").val(id);
            var value_input = '';
            if (type == 0) {
                value_input = '<input type="number" name="value" value="' + value + '">';
            }
            else if (type == 2) {
                value_input = '<textarea style="width: 100%;height: 210px" name="value">' + value + '</textarea>';
            }
            $("#value-value").html(value_input);
            $("#edit_value").modal("show")
        }

        $('.submit').on('click', function () {
            var param = $("#value-form").serialize();
            console.log('请求参数', param, "{{url()->full()}}");
            submit(param);
        });
        $('.switch-btn').on('change', function () {
            var param = {};
            param.id = $(this).attr('id');
            param.value = $(this).is(':checked') ? 1 : 0;
            param._token = "{{ csrf_token() }}";
            console.log('请求参数', param, "{{url()->full()}}");
            submit(param);
        });

        function submit(param) {
            $.ajax({
                url: "{{url()->full()}}",
                type: 'POST',
                data: param,
                success: function (ret) {
                    console.log("ret is:", ret);
                    if (ret.result) {
                        $.Huimodalalert('提交成功！', 2000);
                        setTimeout(function () {
                            location.replace(location.href);
                        }, 2000)
                    } else {
                        $.Huimodalalert('提交失败！', 2000)
                    }
                }
            })
        }
    </script>
    </html>

@endsection