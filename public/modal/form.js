function renderItem($item, $value) {
    $('[data-item=' + $item + ']').html($value);
}

function log($param) {
    console.log($param);
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
        if (installment_type === 'cheque_azad' || installment_type === 'kasr_hoqooq') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.25));
            if (prepaymentAmount <= requiredPrepaymentAmount) {
                prepaymentAmount = requiredPrepaymentAmount;
            }
        }
    }
    if (selected_input === 'calc-prepayment') {
        if (installment_type === 'cheque_azad' || installment_type === 'kasr_hoqooq') {
            requiredPrepaymentAmount = convertNumber(Math.round(total * 0.25));
            if (prepaymentAmount < requiredPrepaymentAmount) {
                $('#calc-prepayment').addClass('is-invalid');
            } else {
                $('#calc-prepayment').removeClass('is-invalid');
            }
        }
    }

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
    if (loanSign === -1) {
        $('#calc-prepayment').addClass('is-invalid');
    }else{
        $('#calc-prepayment').removeClass('is-invalid');
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

    if (installment_type === 'cheque_karmandi') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'hekmat_card') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'safte') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'cheque_azad') {
        wrapper_prepayment.show();
        wrapper_interval.show();
    }
    if (installment_type === 'kasr_hoqooq') {
        wrapper_prepayment.show();
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
                                   selected_input) {
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
    if (selected_input === "installment-type") {
        if (installment_type === 'cheque_karmandi') {
            update_options(input_payment_duration, [
                1, 2, 3, 4, 5, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24
            ]);
            update_options(input_payment_interval, [
                1, 2, 3
            ]);
        }
        if (installment_type === 'hekmat_card') {
            update_options(input_payment_duration, [
                6, 12, 18, 24
            ]);

            update_options(input_payment_interval, [
                1
            ]);
        }
        if (installment_type === 'safte') {
            update_options(input_payment_duration, [
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12
            ]);
            update_options(input_payment_interval, [
                1
            ]);
        }
        if (installment_type === 'cheque_azad') {
            update_options(input_payment_duration, [
                1, 2, 3, 4, 5, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24
            ]);

            update_options(input_payment_interval, [
                1, 2, 3, 4
            ]);
        }
        if (installment_type === 'kasr_hoqooq') {

            update_options(input_payment_duration, [
                1, 2, 3, 4, 5, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24

            ]);
            update_options(input_payment_interval, [
                1
            ]);
        }
    }
    if (installment_type === 'cheque_karmandi') {
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
    if (installment_type === 'hekmat_card') {
        renderResult(
            total_price,
            prepayment_amount,
            0.025,
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
    if (installment_type === 'safte') {
        renderResult(
            total_price,
            prepayment_amount,
            0.0221,
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
    if (installment_type === 'cheque_azad') {
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
    if (installment_type === 'kasr_hoqooq') {
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

$(document).ready(function () {


    let input_installment_type = $('#installment-type');
    let input_total_price = $("#calc-factor-price");
    let input_prepayment_amount = $('#calc-prepayment');
    let input_payment_duration = $('#calc-installment-count');
    let input_payment_interval = $('#calc-installment-interval');

    let effective_inputs = $('.effective_input');
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

    check_prepayment_requirement(input_installment_type);
    perform_calculation_logic(
        input_total_price,
        input_prepayment_amount,
        input_installment_type,
        input_payment_duration,
        input_payment_interval,
        null
    );
    digits_divider();
});


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


$('#installment-calculator').on('show.bs.modal', function (event) {
    // var button = $(event.relatedTarget);// Button that triggered the modal
    // var recipient = button.data('type'); // Extract info from data-* attributes
    // var modal = $(this);
    // modal.find('#installment-type').val(recipient);
});