$(document).ready(function (e) {


    let provinceContainerID = '#province';
    let provinceOptions = $(provinceContainerID);

    let cityContainerID = '#city';
    let cityOptions = $(cityContainerID);
    //Load provinces in the first load
    fetch_provinces(function (response) {
        if (response.length > 0) {
            let first_provinceID = null;
            response.forEach(function (v) {
                provinceOptions.append("<option value='" + v.provinceId + "'>" + v.provinceName + "</option>");
                if (first_provinceID === null) {
                    first_provinceID = v.provinceId;
                }
            });
            if (first_provinceID !== null) {
                fetch_cities(first_provinceID, function (response) {
                    cityOptions.html("");
                    if (response.length > 0) {
                        response.forEach(function (v) {
                            cityOptions.append("<option value='" + v.cityID + "'>" + v.cityName + "</option>");
                        });
                    }
                })
            }
        }
    });


    provinceOptions.on('change', function (e) {
        let selectedProvince = $(this).val();
        fetch_cities(selectedProvince, function (response) {
            cityOptions.html("");
            if (response.length > 0) {
                response.forEach(function (v) {
                    cityOptions.append("<option value='" + v.cityID + "'>" + v.cityName + "</option>");
                });
            }
        })
    });
});


function fetch_cities(province_id, _callBack) {


    let ajaxUrl = applicationDomain + '/repository/location/get-cities/' + province_id;
    $.ajax({
        url: ajaxUrl,
        data: [],
        success: function (response) {
            _callBack(response);
        }
    });
}

function fetch_provinces(_callBack) {

    let ajaxUrl = applicationDomain + '/repository/location/get-provinces';
    $.ajax({
        url: ajaxUrl,
        data: [],
        success: function (response) {
            _callBack(response);
        }
    });
}