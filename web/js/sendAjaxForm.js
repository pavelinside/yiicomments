function sendAjaxForm(form){
  return new Promise(function(resolve, reject){
    var data = form.serialize();
    // отправляем данные на сервер
    $.ajax({
      url: form.attr('action'),
      type: form.attr('method'),
      data: data
    })
    .done(function(data) {
      resolve(data);
    })
    .fail(function () {
      reject({error: 'Произошла ошибка при отправке данных!'});
    })
  });
}