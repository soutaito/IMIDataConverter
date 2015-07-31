function appendTemplate(input){
    if(input.val() != ''){
        $.ajax({
            url:productPath + 'project/excerpt/' + input.val(),
            type:"get",
            dataType:"html"
        }).then(function(response) {
            $('#project_load').html(response);
        });
    }else{
        $('#project_load').html('');
    }
}

$(function(){
    $('#project-select').on('change', function(){
        appendTemplate($(this));
    });

    $('.project_recommend button').on('click', function(e){
        e.preventDefault();
        appendTemplate($(this));
    });

    $('#delete_project').click(function(){
        if(confirm('このプロジェクトを削除します。よろしいですか？')){
            return true;
        }else{
            return false;
        }
    })
});

