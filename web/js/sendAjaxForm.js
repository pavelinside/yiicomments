function sendAjaxForm(form){
  return new Promise(function(resolve, reject){
    var formData = new FormData();
    var data = $(form).serializeArray().forEach(function(elem){
      formData.append(elem.name, elem.value);
    });
    // files
    $(form).find('input[type="file"]').each(function(){
      if(this.files && this.files.length > 0){
        for(let i =0, ilen = this.files.length; i < ilen; i++){
          formData.set(this.name, this.files[i], this.files[i].name);
        }
      }
    });

    // отправляем данные на сервер
    $.ajax({
      url: form.action,
      type: form.method,
      data: formData,
      processData: false,
      contentType: false
    })
    .done(function(data) {
      resolve(data);
    })
    .fail(function () {
      reject({error: 'Произошла ошибка при отправке данных!'});
    })
  });
}