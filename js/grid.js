$(function(){
    $.ajaxSetup({
        success: function(data){
            if(data.redirect){
                $.get(data.redirect);
            }
            if(data.snippets){
                for (var snippet in data.snippets){
                    $("#"+snippet).html(data.snippets[snippet]);
                }
            }
        }
    });

    $(".grid-flash-hide").live("click", function(){
        $(this).parent().parent().fadeOut(300);
    });

    $(".grid-select-all").live("click", function(){
        var checkboxes =  $(this).parents("thead").siblings("tbody").children("tr:not(.grid-subgrid-row)").find("td input:checkbox.grid-action-checkbox");
        if($(this).is(":checked")){
            $(checkboxes).attr("checked", "checked");
        }else{
            $(checkboxes).removeAttr("checked");
        }
    });

    $('.grid a.grid-ajax:not(.grid-confirm)').live('click', function (event) {
        event.preventDefault();
        $.get(this.href);
    });

    $('.grid a.grid-confirm:not(.grid-ajax)').live('click', function (event) {
        var answer = confirm($(this).data("grid-confirm"));
        return answer;
    });

    $('.grid a.grid-confirm.grid-ajax').live('click', function (event) {
        event.preventDefault();
        var answer = confirm($(this).data("grid-confirm"));
        if(answer){
            $.get(this.href);
        }
    });

    $(".grid-gridForm").find("input[type=submit]").live("click", function(){
        $(this).addClass("grid-gridForm-clickedSubmit");
    });


    $(".grid-gridForm").live("submit", function(event){
        event.preventDefault();
        var button = $(".grid-gridForm-clickedSubmit");
        var selectName = $(button).data("select");
        var selected = $("select[name=\""+selectName+"\"] option:selected").data('grid-confirm');
        if(selected){
            var answer = confirm(selected);
            if(answer){
                $.post(this.action, $(this).serialize()+"&"+$(button).attr("name")+"="+$(button).val());
                $(button).removeClass("grid-gridForm-clickedSubmit");
            }
        }else{
            $.post(this.action, $(this).serialize()+"&"+$(button).attr("name")+"="+$(button).val());
            $(button).removeClass("grid-gridForm-clickedSubmit");
        }
    });

    $(".grid-autocomplete").live('keydown.autocomplete', function(){
        var gridName = $(this).data("gridname");
        var column = $(this).data("column");
        var link = $(this).data("link");
        $(this).autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: link,
                    data: gridName+'-term='+request.term+'&'+gridName+'-column='+column,
                    dataType: "json",
                    method: "post",
                    success: function(data) {
                        response(data.payload);
                    }
                });
            },
            delay: 100,
            open: function() { $('.ui-menu').width($(this).width()) }
        });
    });

    $(".grid-changeperpage").live("change", function(){
        $.get($(this).data("link"), $(this).data("gridname")+"-perPage="+$(this).val());
    });

    function hidePerPageSubmit()
    {
        $(".grid-perpagesubmit").hide();
    }
    hidePerPageSubmit();

    function setDatepicker()
    {
        $.datepicker.regional['cs'] = {
            closeText: 'Zavřít',
            prevText: '&#x3c;Dříve',
            nextText: 'Později&#x3e;',
            currentText: 'Nyní',
            monthNames: ['leden','únor','březen','duben','květen','červen',
                'červenec','srpen','září','říjen','listopad','prosinec'],
            monthNamesShort: ['led','úno','bře','dub','kvě','čer',
                'čvc','srp','zář','říj','lis','pro'],
            dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
            dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
            dayNamesMin: ['ne','po','út','st','čt','pá','so'],
            weekHeader: 'Týd',
            dateFormat: 'yy-mm-dd',
            constrainInput: false,
            firstDay: 1,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ''};
        $.datepicker.setDefaults($.datepicker.regional['cs']);

        $(".grid-datepicker").each(function(){
            if(($(this).val() != "")){
                var date = $.datepicker.formatDate('yy-mm-dd', new Date($(this).val()));
            }
            $(this).datepicker();
            $(this).datepicker({ constrainInput: false});
        });
    }
    setDatepicker();

    $(this).ajaxStop(function(){
        setDatepicker();
        hidePerPageSubmit();
    });
});