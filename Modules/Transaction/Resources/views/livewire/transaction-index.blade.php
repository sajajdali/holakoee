<div>
    @error('success')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <span class="alert-inner--icon me-2"><i class="fe fe-thumbs-up"></i></span> <span
                class="alert-inner--text">{{ $message }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"> <span
                    aria-hidden="true">×</span> </button>
        </div>
    @enderror
    @error('areUsure')
        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert"> <span
                class="alert-inner--icon me-2"><i class="fe fe-slash"></i></span> <span
                class="alert-inner--text"><strong>اخطار!</strong> {{ $message }}</span> <button
                @click="$dispatch('deleteHasBeenconfiremd')" class="btn btn-sm btn-danger">بله</button> <button
                type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"> <span
                    aria-hidden="true">×</span> </button> </div>
    @enderror
    <div class="row row-sm mt-5">
        <div class="col-lg-12">
            <div class="card custom-card">
                <div class="card-header border-bottom">
                    <h3 class="card-title">درخواست های رژیم</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-nowrap text-md-nowrap table-bordered">
                            <thead>
                                <tr class="text-center">
                                    <th>#</th>
                                    <th>نام کاربر</th>
                                    <th>وضعیت</th>
                                    <th>نوع تراکنش</th>
                                    <th>هزینه</th>
                                    <th>تخفیف</th>
                                    <th>هزینه ی کل</th>
                                    <th>زمان </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr class="text-center {{$transaction->getRawOriginal('status') == 0 ? 'table-danger' : 'table-success'}}  ">
                                        <td>{{ $transaction->id }}</td>
                                        <td>{{ $transaction->user->full_name }}</td>
                                        <td> {{ $transaction->paid_by->getName() }}</td>
                                        <td> {{ $transaction->status->getName() }}</td>
                                        <td> {{ number_format($transaction->cost) }}</td>
                                        <td> {{ number_format($transaction->discount) }}</td>
                                        <td> {{ number_format($transaction->total_cost) }}</td>
                                        <td> {{ verta($transaction->created_at)->format('Y/m/d ساعت H:i دقیقه') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex mt-3 justify-content-center">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
