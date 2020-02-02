function renderItem($item, $value) {
    $('[data-item=' + $item + ']').html($value);
}

function log($param) {
    console.log($param);
}

function show_limit_alert($amount) {
    let message = "حداکثر سقف پرداخت ";
    message += $amount;
    message += " می باشد.";
    $("#calc-limit-alert").html(message);

}

function hide_limit_alert() {
    $("#calc-limit-alert").html("");
}

function cal_loanAmount(total, prepaymentAmount) {
    return total - prepaymentAmount;
}

function calc_monthly_interest(loanAmount, monthlyInterestPercentage) {
    return loanAmount * monthlyInterestPercentage;
}

function calc_total_interest_amount(monthlyInterestAmount, paymentDuration) {
    return monthlyInterestAmount * paymentDuration;
}

function calc_total_payoff(loanAmount, totalInterestAmount) {
    return loanAmount + totalInterestAmount;
}

function calc_monthly_payoff(totalPayOff, paymentDuration) {
    return totalPayOff / paymentDuration;
}

function calc_prepayment_amount(total, prePaymentPercentage) {
    return total * prePaymentPercentage;
}

function calc_prepayment_percentage(prepaymentAmount, total) {
    return (prepaymentAmount / total) * 100;
}

function convertNumber($value) {
    // log(parseFloat($value));
    // return $value;
    // return parseFloat($value);
    return (parseInt($value));
}

function renderResult(
    $total,
    $prepaymentAmount,
    $interestPercentage,
    $paymentDuration,
    $paymentInterval,
    input_total_price,
    input_prepayment_amount,
    input_payment_duration,
    input_payment_interval,
    installment_type = null,
    selected_input
) {
    let prepaymentAmount = convertNumber($prepaymentAmount);
    let requiredPrepaymentAmount = 0;
    /*
       Inputs
     */
    let total = ($total);
    if (selected_input !== 'calc-prepayment') {
        if (installment_type === 'cheque') {
            // requiredPrepaymentAmount = convertNumber(Math.round(total * 0));
            // if (prepaymentAmount <= requiredPrepaymentAmount) {
            //     prepaymentAmount = requiredPrepaymentAmount;
            //
            // }
        }
        if (installment_type === 'safte') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.25));
            if (prepaymentAmount <= requiredPrepaymentAmount) {
                prepaymentAmount = requiredPrepaymentAmount;

            }
        }
        if (installment_type === 'by_cheque') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.25));
            if (prepaymentAmount <= requiredPrepaymentAmount) {
                prepaymentAmount = requiredPrepaymentAmount;

            }
        }
        if (installment_type === 'arad_credit') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.25));
            if (prepaymentAmount <= requiredPrepaymentAmount) {
                prepaymentAmount = requiredPrepaymentAmount;

            }
        }
        if (installment_type === 'khodro') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.50));
            if (prepaymentAmount <= requiredPrepaymentAmount) {
                prepaymentAmount = requiredPrepaymentAmount;

            }
        }
    }

    // if (selected_input === 'calc-prepayment') {


    let monthlyInterestPercentage = ($interestPercentage);
    let paymentDuration = convertNumber($paymentDuration);
    let paymentInterval = convertNumber($paymentInterval);

    let loanAmount = convertNumber(cal_loanAmount(total, prepaymentAmount));
    let monthlyInterestAmount = convertNumber(calc_monthly_interest(loanAmount, monthlyInterestPercentage));
    let totalInterestAmount = convertNumber(calc_total_interest_amount(monthlyInterestAmount, paymentDuration));
    let totalPayOff = convertNumber(calc_total_payoff(loanAmount, totalInterestAmount));
    let monthlyPayOff = convertNumber(paymentInterval * calc_monthly_payoff(totalPayOff, paymentDuration));
    let prePaymentPercentage = convertNumber(calc_prepayment_percentage(prepaymentAmount, total));
    let loanSign = Math.sign(loanAmount);

    // if (loanSign === -1) {
    //     $('#calc-prepayment').addClass('is-invalid');
    // } else {
    //     $('#calc-prepayment').removeClass('is-invalid');
    // }

    if (true) {
        if (installment_type === 'cheque') {

            let limit = 9999999999999999;
            if (total > limit) {
                show_limit_alert(limit);
            } else {
                hide_limit_alert();
            }

            requiredPrepaymentAmount = convertNumber(Math.round(0));
            if (prepaymentAmount < requiredPrepaymentAmount || loanSign === -1) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }

        }
        if (installment_type === 'safte') {


            let limit = 250000000;
            if (total > limit) {
                show_limit_alert(limit);
            } else {
                hide_limit_alert();
            }

            requiredPrepaymentAmount = convertNumber(Math.round(0));
            if (prepaymentAmount < requiredPrepaymentAmount || loanSign === -1) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }
        }
        if (installment_type === 'arad_credit') {


            let limit = 8000000;
            if (total > limit) {
                show_limit_alert(limit);
            } else {
                hide_limit_alert();
            }

            requiredPrepaymentAmount = convertNumber(Math.round(0));
            if (prepaymentAmount < requiredPrepaymentAmount || loanSign === -1) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }
        }
        if (installment_type === 'khodro') {

            let limit = 9999999999999999;
            if (total > limit) {
                show_limit_alert(limit);
            } else {
                hide_limit_alert();
            }

            requiredPrepaymentAmount = convertNumber(Math.round(0));
            if (prepaymentAmount < requiredPrepaymentAmount || loanSign === -1) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }
        }
        if (installment_type === 'by_cheque') {

            let limit = 9999999999999999;
            if (total > limit) {
                show_limit_alert(limit);
            } else {
                hide_limit_alert();
            }

            requiredPrepaymentAmount = convertNumber(Math.round(0));
            if (prepaymentAmount < requiredPrepaymentAmount || loanSign === -1) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }
        }
    }
    update_input_values(
        input_total_price,
        total,
        input_payment_duration,
        paymentDuration,
        input_payment_interval,
        paymentInterval,
        input_prepayment_amount,
        prepaymentAmount
    );
    if (total) {
        renderItem('totalPrice', function () {
            return divide_number(total);
        });

        renderItem('prePayment', function () {
            return divide_number(prepaymentAmount);
        });

        renderItem('prePaymentPercent', function () {
            return prePaymentPercentage;
        });

        renderItem('installmentLoan', function () {
            return divide_number(loanAmount.toString());
        });

        renderItem('installmentPayment', function () {
            return divide_number(parseInt(monthlyPayOff).toString());
        });
        renderItem('monthCount', function () {
            return paymentDuration;
        });
        renderItem('eachMonthPayment', function () {
            return divide_number(monthlyPayOff.toString());
        });
        renderItem('paymentIntervalCount', function () {
            return paymentInterval;
        });

        renderItem('extraPayment', function () {
            return divide_number(totalInterestAmount.toString());
        });
    } else {
        renderItem('totalPrice', function () {
            return '-';
        });

        renderItem('prePayment', function () {
            return '-';
        });

        renderItem('prePaymentPercent', function () {
            return '-';
        });

        renderItem('installmentLoan', function () {
            return '-';
        });

        renderItem('installmentPayment', function () {
            return '-';
        });
        renderItem('monthCount', function () {
            return '-';
        });
        renderItem('eachMonthPayment', function () {
            return '-';
        });
        renderItem('paymentIntervalCount', function () {
            return '-';
        });

        renderItem('extraPayment', function () {
            return '-';
        });
    }
}

function check_prepayment_requirement(input_installment_type) {

    let installment_type = input_installment_type.val();

    let wrapper_prepayment = $('#prepayment-wrapper');
    let wrapper_interval = $('#interval-wrapper');

    if (installment_type === 'cheque') {
        $("#calc-prepayment-alert").hide();
        wrapper_prepayment.show();
        wrapper_interval.show();
    } else {
        $("#calc-prepayment-alert").show();
    }
    if (installment_type === 'safte') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'arad_credit') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'khodro') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'by_cheque') {
        wrapper_prepayment.show()
        wrapper_interval.show();
    }
}

function update_input_values(
    input_total_price,
    total_price,
    input_payment_duration,
    payment_duration,
    input_payment_interval,
    payment_interval,
    input_prepayment_amount,
    prepayment_amount
) {

    input_total_price.val(divide_number(total_price));
    input_payment_duration.val(divide_number(payment_duration));
    input_payment_interval.val(divide_number(payment_interval));
    input_prepayment_amount.val(divide_number(prepayment_amount));
}

function update_options($input, $items) {
    $input.html("");
    $items.forEach(function (k, v) {
        let element = "<option value=\"" + k + "\">" + k + "</option>";
        $input.append(element);
    });
}


function perform_calculation_logic(input_total_price,
                                   input_prepayment_amount,
                                   input_installment_type,
                                   input_payment_duration,
                                   input_payment_interval,
                                   selected_input,
                                   initial = false) {

    let prepayment_amount = 0;
    let payment_interval = 1;
    let total_price = getVal(input_total_price);
    if (input_prepayment_amount.is(':visible')) {
        prepayment_amount = getVal(input_prepayment_amount);
    }
    let installment_type = input_installment_type.val();
    let payment_duration = input_payment_duration.val();
    if (input_payment_interval.is(':visible')) {
        payment_interval = input_payment_interval.val();
    }
    if (selected_input === "installment-type" || initial) {
        if (installment_type === 'cheque') {

            update_options(input_payment_duration, [
                6, 12, 18, 24, 30, 36
            ]);
            update_options(input_payment_interval, [
                1, 2, 3
            ]);
            $("#calc-prepayment-alert").hide();
        } else {
            $("#calc-prepayment-alert").show();
        }

        if (installment_type === 'safte') {
            update_options(input_payment_duration, [
                6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24
            ]);
            update_options(input_payment_interval, [
                1, 2, 3
            ]);
        }
        if (installment_type === 'arad_credit') {
            update_options(input_payment_duration, [
                6, 12
            ]);
            update_options(input_payment_interval, [
                1
            ]);
        }
        if (installment_type === 'khodro') {
            update_options(input_payment_duration, [
                6, 12, 18, 24, 30, 36, 42, 48
            ]);
            update_options(input_payment_interval, [
                3
            ]);
        }
        if (installment_type === 'by_cheque') {
            update_options(input_payment_duration, [
                6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24
            ]);
            update_options(input_payment_interval, [
                1, 2, 3, 4
            ]);
        }
    }
    if (installment_type === 'cheque') {


        renderResult(
            total_price,
            prepayment_amount,
            0.025,
            payment_duration,
            payment_interval,
            input_total_price,
            input_prepayment_amount,
            input_payment_duration,
            input_payment_interval,
            installment_type,
            selected_input
        );

    }
    if (installment_type === 'safte') {
        renderResult(
            total_price,
            prepayment_amount,
            0.035,
            payment_duration,
            1,
            input_total_price,
            input_prepayment_amount,
            input_payment_duration,
            input_payment_interval,
            installment_type,
            selected_input
        );
    }
    if (installment_type === 'arad_credit') {
        renderResult(
            total_price,
            prepayment_amount,
            0.020,
            payment_duration,
            1,
            input_total_price,
            input_prepayment_amount,
            input_payment_duration,
            input_payment_interval,
            installment_type,
            selected_input
        );
    }
    if (installment_type === 'khodro') {
        renderResult(
            total_price,
            prepayment_amount,
            0.025,
            payment_duration,
            payment_interval,
            input_total_price,
            input_prepayment_amount,
            input_payment_duration,
            input_payment_interval,
            installment_type,
            selected_input
        );
    }
    if (installment_type === 'by_cheque') {


        if (payment_duration <= 12) {

            renderResult(
                total_price,
                prepayment_amount,
                0.04,
                payment_duration,
                payment_interval,
                input_total_price,
                input_prepayment_amount,
                input_payment_duration,
                input_payment_interval,
                installment_type,
                selected_input
            );

        } else {

            renderResult(
                total_price,
                prepayment_amount,
                0.035,
                payment_duration,
                payment_interval,
                input_total_price,
                input_prepayment_amount,
                input_payment_duration,
                input_payment_interval,
                installment_type,
                selected_input
            );

        }
    }
}


function getVal($input) {
    return $($input).val().replace(/،/g, '');
}

function digits_divider() {
    $('input').on('keyup', function () {
        if ($(this).attr('id') === 'calc-factor-price') {
            let currentVal = $(this).val().replace(/،/g, '');
            $(this).val(divide_number(currentVal));
        }
        if ($(this).attr('id') === 'calc-prepayment') {
            let currentVal = $(this).val().replace(/،/g, '');
            $(this).val(divide_number(currentVal));
        }
    });
}

// function bind_value($input = null, $element, $value = null) {
//     $input.on('input', function () {
//         let input = $input.val();
//         if ($value !== null) {
//             $element.html($value);
//         } else {
//             $element.html(input);
//         }
//     });
// }

function divide_number($rawNumber) {

    let $dividedReversedNumber = [];
    let arrayedNumber = $rawNumber.toString().split('');
    let ReversedArrayNumber = arrayedNumber.reverse();

    for (let $i = 1; $i <= ReversedArrayNumber.length; $i++) {
        $dividedReversedNumber.push(ReversedArrayNumber[$i - 1]);
        if ($i % 3 === 0) {
            $dividedReversedNumber.push('،');
        }
    }
    let correctArray = $dividedReversedNumber.reverse();
    let outPut = correctArray.toString();
    correctArray.forEach(function () {
        outPut = outPut.replace(',', '');
    });

    if (outPut.charAt(0) === '،') {
        outPut = outPut.substr(1);
    }

    return outPut;
}


let modalContentProduct = "<div class=\"modal fade dir-rtl\" id=\"installment-calculator\" tabindex=\"-1\" role=\"dialog\" aria-hidden=\"true\">" +
    "    <div class=\"modal-dialog modal-dialog-centered modal-lg\" role=\"document\"> " +
    "        <div class=\"modal-content\"> " +
    "            <div class=\"modal-header\"> " +
    "                <h5 class=\"modal-title\" id=\"exampleModalLongTitle\">فرم محاسبه اقساط کالا</h5> " +
    "                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"> " +
    "                    <span aria-hidden=\"true\">&times;</span> " +
    "                </button> " +
    "            </div> " +
    "            <div class=\"modal-body text-center p-0\"> " +
    " " +
    "                <div class=\"calculator\"> " +
    "                    <p> با این فرم به سادگی میزان مبلغ اقساط خود را محاسبه کنید (کلیه مبالغ به تومان می‌باشد) </p> " +
    " " +
    "                    <div class=\"row\"> " +
    "                        <div class=\"col-xs-12 col-md-6\"> " +
    "                            <div class=\"form-row text-right\"> " +
    " " +
    "                                <div class=\"form-group col-xs-12 col-md-6 \"> " +
    "                                    <label>مبلغ فاکتور</label> " +
    "                                    <input type=\"text\" class=\"form-control effective_input\" data-required=\"true\" " +
    "                                           id=\"calc-factor-price\" " +
    "                                           placeholder=\"مبلغ به تومان\"> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-12 col-md-6\"> " +
    "                                    <label>نوع پرداخت</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"installment-type\"> " +
    "                                        <option value=\"cheque\">چک آراد موبایل</option> " +
    "                                        <option value=\"safte\">سفته و کسر از حقوق</option> " +
    "                                        <option value=\"arad_credit\">کارت اعتباری آراد</option> " +
    "                                        <option value=\"by_cheque\">چک (بای چک)</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-12 col-md-12\" id=\"prepayment-wrapper\"> " +
    "                                    <label>پیش پرداخت</label> " +
    "                                    <input type=\"text\" class=\"form-control effective_input\" data-required=\"true\" " +
    "                                           id=\"calc-prepayment\" " +
    "                                           placeholder=\"به صورت پیش فرض ۲۵ درصد\"> " +
    "                                    <small class=\"form-text\" id=\"calc-prepayment-alert\">پیش پرداخت " +
    "                                        حداقل ۲۵ " +
    "                                        درصد مبلغ فاکتور می " +
    "                                        باشد. " +
    "                                    </small> " +
    "                                    <strong class=\"form-text text-danger\" id=\"calc-limit-alert\">" +
    "                                    </strong> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-6 col-md-6\"> " +
    "                                    <label>تعداد اقساط</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"calc-installment-count\"> " +
    "                                        <option value=\"6\">۶</option> " +
    "                                        <option value=\"7\">۷</option> " +
    "                                        <option value=\"8\">۸</option> " +
    "                                        <option value=\"9\">۹</option> " +
    "                                        <option value=\"10\">۱۰</option> " +
    "                                        <option value=\"11\">۱۱</option> " +
    "                                        <option value=\"12\">۱۲</option> " +
    "                                        <option value=\"13\">۱۳</option> " +
    "                                        <option value=\"14\">۱۴</option> " +
    "                                        <option value=\"15\">۱۵</option> " +
    "                                        <option value=\"16\">۱۶</option> " +
    "                                        <option value=\"17\">۱۷</option> " +
    "                                        <option value=\"18\">۱۸</option> " +
    "                                        <option value=\"19\">۱۹</option> " +
    "                                        <option value=\"20\">۲۰</option> " +
    "                                        <option value=\"21\">۲۱</option> " +
    "                                        <option value=\"22\">۲۲</option> " +
    "                                        <option value=\"23\">۲۳</option> " +
    "                                        <option value=\"24\">۲۴</option> " +
    "                                        <option value=\"25\">۲۵</option> " +
    "                                        <option value=\"26\">۲۶</option> " +
    "                                        <option value=\"27\">۲۷</option> " +
    "                                        <option value=\"28\">۲۸</option> " +
    "                                        <option value=\"29\">۲۹</option> " +
    "                                        <option value=\"30\">۳۰</option> " +
    "                                        <option value=\"31\">۳۱</option> " +
    "                                        <option value=\"32\">۳۲</option> " +
    "                                        <option value=\"33\">۳۳</option> " +
    "                                        <option value=\"34\">۳۴</option> " +
    "                                        <option value=\"35\">۳۵</option> " +
    "                                        <option value=\"36\">۳۶</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-6 col-md-6\" id=\"interval-wrapper\"> " +
    "                                    <label>فاصله چک ها</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"calc-installment-interval\"> " +
    "                                        <option value=\"1\">۱</option> " +
    "                                        <option value=\"2\">۲</option> " +
    "                                        <option value=\"3\">۳</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                            </div> " +
    "                        </div> " +
    "                        <div class=\"col-xs-12 col-md-6 d-flex align-items-center mt-5 mt-md-0\"> " +
    " " +
    "                            <div class=\"invoice\"> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ کل فاکتور</div> " +
    "                                    <div class=\"value\" data-item=\"totalPrice\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ پیش پرداخت</div> " +
    "                                    <div class=\"value\" data-item=\"prePayment\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">درصد پیش پرداخت</div> " +
    "                                    <div class=\"value\" data-item=\"prePaymentPercent\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ وام شما</div> " +
    "                                    <div class=\"value\" data-item=\"installmentLoan\">-</div> " +
    "                                </div> " +
    "                                <!--      <div class=\"invoice-row\"> " +
    "                                          <div class=\"key\">مبلغ هر قسط</div> " +
    "                                          <div class=\"value\" data-item=\"installmentPayment\">-</div> " +
    "                                      </div> --> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">طول دوره بازپراخت (به ماه)</div> " +
    "                                    <div class=\"value\" data-item=\"monthCount\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">فاصله اقساط (به ماه)</div> " +
    "                                    <div class=\"value\" data-item=\"paymentIntervalCount\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ هر قسط (چک)</div> " +
    "                                    <div class=\"value\" data-item=\"eachMonthPayment\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">کل سود وام</div> " +
    "                                    <div class=\"value\" data-item=\"extraPayment\">-</div> " +
    "                                </div> " +
    "                            </div> " +
    "                        </div> " +
    "                    </div> " +
    "                </div> " +
    "            </div>" +
    "        </div> " +
    "    </div> " +
    "</div>";


let modalContentCar = "<div class=\"modal fade dir-rtl\" id=\"installment-calculator\" tabindex=\"-1\" role=\"dialog\" aria-hidden=\"true\">" +
    "    <div class=\"modal-dialog modal-dialog-centered modal-lg\" role=\"document\"> " +
    "        <div class=\"modal-content\"> " +
    "            <div class=\"modal-header\"> " +
    "                <h5 class=\"modal-title\" id=\"exampleModalLongTitle\">فرم محاسبه اقساط خودرو</h5> " +
    "                <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"> " +
    "                    <span aria-hidden=\"true\">&times;</span> " +
    "                </button> " +
    "            </div> " +
    "            <div class=\"modal-body text-center p-0\"> " +
    " " +
    "                <div class=\"calculator\"> " +
    "                    <p> با این فرم به سادگی میزان مبلغ اقساط خود را محاسبه کنید (کلیه مبالغ به تومان می‌باشد) </p> " +
    " " +
    "                    <div class=\"row\"> " +
    "                        <div class=\"col-xs-12 col-md-6\"> " +
    "                            <div class=\"form-row text-right\"> " +
    " " +
    "                                <div class=\"form-group col-xs-12 col-md-6 \"> " +
    "                                    <label>مبلغ فاکتور</label> " +
    "                                    <input type=\"text\" class=\"form-control effective_input\" data-required=\"true\" " +
    "                                           id=\"calc-factor-price\" " +
    "                                           placeholder=\"مبلغ به تومان\"> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-12 col-md-6\"> " +
    "                                    <label>نوع پرداخت</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"installment-type\"> " +
    "                                        <option value=\"khodro\">مخصوص خودرو</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-12 col-md-12\" id=\"prepayment-wrapper\"> " +
    "                                    <label>پیش پرداخت</label> " +
    "                                    <input type=\"text\" class=\"form-control effective_input\" data-required=\"true\" " +
    "                                           id=\"calc-prepayment\" " +
    "                                           placeholder=\"به صورت پیش فرض ۲۵ درصد\"> " +
    "                                    <small class=\"form-text\" id=\"calc-prepayment-alert\">پیش پرداخت " +
    "                                        حداقل ۵۰ " +
    "                                        درصد مبلغ فاکتور می " +
    "                                        باشد. " +
    "                                    </small> " +
    "                                    <strong class=\"form-text text-danger\" id=\"calc-limit-alert\">" +
    "                                    </strong> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-6 col-md-6\"> " +
    "                                    <label>تعداد اقساط</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"calc-installment-count\"> " +
    "                                        <option value=\"1\">۱</option> " +
    "                                        <option value=\"2\">۲</option> " +
    "                                        <option value=\"3\">۳</option> " +
    "                                        <option value=\"4\">۴</option> " +
    "                                        <option value=\"5\">۵</option> " +
    "                                        <option value=\"6\">۶</option> " +
    "                                        <option value=\"7\">۷</option> " +
    "                                        <option value=\"8\">۸</option> " +
    "                                        <option value=\"9\">۹</option> " +
    "                                        <option value=\"10\">۱۰</option> " +
    "                                        <option value=\"11\">۱۱</option> " +
    "                                        <option value=\"12\">۱۲</option> " +
    "                                        <option value=\"13\">۱۳</option> " +
    "                                        <option value=\"14\">۱۴</option> " +
    "                                        <option value=\"15\">۱۵</option> " +
    "                                        <option value=\"16\">۱۶</option> " +
    "                                        <option value=\"17\">۱۷</option> " +
    "                                        <option value=\"18\">۱۸</option> " +
    "                                        <option value=\"19\">۱۹</option> " +
    "                                        <option value=\"20\">۲۰</option> " +
    "                                        <option value=\"21\">۲۱</option> " +
    "                                        <option value=\"22\">۲۲</option> " +
    "                                        <option value=\"23\">۲۳</option> " +
    "                                        <option value=\"24\">۲۴</option> " +
    "                                        <option value=\"25\">۲۵</option> " +
    "                                        <option value=\"26\">۲۶</option> " +
    "                                        <option value=\"27\">۲۷</option> " +
    "                                        <option value=\"28\">۲۸</option> " +
    "                                        <option value=\"29\">۲۹</option> " +
    "                                        <option value=\"30\">۳۰</option> " +
    "                                        <option value=\"31\">۳۱</option> " +
    "                                        <option value=\"32\">۳۲</option> " +
    "                                        <option value=\"33\">۳۳</option> " +
    "                                        <option value=\"34\">۳۴</option> " +
    "                                        <option value=\"35\">۳۵</option> " +
    "                                        <option value=\"36\">۳۶</option> " +
    "                                        <option value=\"37\">۳۷</option> " +
    "                                        <option value=\"38\">۳۸</option> " +
    "                                        <option value=\"39\">۳۹</option> " +
    "                                        <option value=\"40\">۴۰</option> " +
    "                                        <option value=\"41\">۴۱</option> " +
    "                                        <option value=\"42\">۴۲</option> " +
    "                                        <option value=\"43\">۴۳</option> " +
    "                                        <option value=\"44\">۴۴</option> " +
    "                                        <option value=\"45\">۴۵</option> " +
    "                                        <option value=\"46\">۴۶</option> " +
    "                                        <option value=\"47\">۴۷</option> " +
    "                                        <option value=\"48\">۴۸</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                                <div class=\"form-group col-xs-6 col-md-6\" id=\"interval-wrapper\"> " +
    "                                    <label>فاصله چک ها</label> " +
    "                                    <select class=\"form-control effective_input\" id=\"calc-installment-interval\"> " +
    "                                        <option value=\"3\">۳</option> " +
    "                                    </select> " +
    "                                </div> " +
    "                            </div> " +
    "                        </div> " +
    "                        <div class=\"col-xs-12 col-md-6 d-flex align-items-center mt-5 mt-md-0\"> " +
    " " +
    "                            <div class=\"invoice\"> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ کل فاکتور</div> " +
    "                                    <div class=\"value\" data-item=\"totalPrice\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ پیش پرداخت</div> " +
    "                                    <div class=\"value\" data-item=\"prePayment\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">درصد پیش پرداخت</div> " +
    "                                    <div class=\"value\" data-item=\"prePaymentPercent\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ وام شما</div> " +
    "                                    <div class=\"value\" data-item=\"installmentLoan\">-</div> " +
    "                                </div> " +
    "                                <!--      <div class=\"invoice-row\"> " +
    "                                          <div class=\"key\">مبلغ هر قسط</div> " +
    "                                          <div class=\"value\" data-item=\"installmentPayment\">-</div> " +
    "                                      </div> --> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">طول دوره بازپراخت (به ماه)</div> " +
    "                                    <div class=\"value\" data-item=\"monthCount\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">فاصله اقساط (به ماه)</div> " +
    "                                    <div class=\"value\" data-item=\"paymentIntervalCount\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">مبلغ هر قسط (چک)</div> " +
    "                                    <div class=\"value\" data-item=\"eachMonthPayment\">-</div> " +
    "                                </div> " +
    "                                <div class=\"invoice-row\"> " +
    "                                    <div class=\"key\">کل سود وام</div> " +
    "                                    <div class=\"value\" data-item=\"extraPayment\">-</div> " +
    "                                </div> " +
    "                            </div> " +
    "                        </div> " +
    "                    </div> " +
    "                </div> " +
    "            </div>" +
    "        </div> " +
    "    </div> " +
    "</div>";

// let input_installment_type = $('#installment-type');
// let input_total_price = $("#calc-factor-price");
// let input_prepayment_amount = $('#calc-prepayment');
// let input_payment_duration = $('#calc-installment-count');
// let input_payment_interval = $('#calc-installment-interval');
// let label_payment_limit_alert = $("#calc-limit-alert");
//
// let effective_inputs = $('.effective_input');


let input_installment_type;
let input_total_price;
let input_prepayment_amount;
let input_payment_duration;
let input_payment_interval;
let label_payment_limit_alert;

let effective_inputs;

$(".modal-invoker").on('click touch', function (e) {
    let invoke = $(this).data('invoke');
    let recipient = $(this).data('type'); // Extract info from data-* attributes


    if (invoke === 'car') {
        $('body').prepend(modalContentCar);
    }
    if (invoke === 'product') {
        $('body').prepend(modalContentProduct);
    }

    input_installment_type = $('#installment-type');
    input_total_price = $("#calc-factor-price");
    input_prepayment_amount = $('#calc-prepayment');
    input_payment_duration = $('#calc-installment-count');
    input_payment_interval = $('#calc-installment-interval');
    label_payment_limit_alert = $("#calc-limit-alert");

    effective_inputs = $('.effective_input');
    input_installment_type.val(recipient);

    check_prepayment_requirement(input_installment_type);
    perform_calculation_logic(
        input_total_price,
        input_prepayment_amount,
        input_installment_type,
        input_payment_duration,
        input_payment_interval,
        null,
        true
    );
    effective_inputs.on('input change', function () {
        if (input_prepayment_amount.val() === "") {
            input_prepayment_amount.val(0)
        }
        check_prepayment_requirement(input_installment_type);
        perform_calculation_logic(
            input_total_price,
            input_prepayment_amount,
            input_installment_type,
            input_payment_duration,
            input_payment_interval,
            $(this).attr('id')
        );
    });

    digits_divider();
});
