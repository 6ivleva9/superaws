"use strict";

//	получение данных из базы и формирование таблицы
function refresh_table() {
	var clb = function (data) {
		//	если все таки от сервера чтото пришло
		if (data) {
			console.log('get_all_data data:', data);
			//	если есть записи о людях
			if (data.id_user) {
				//	очистка тела таблицы
				$('.table tbody').empty();
				for (var i in data.id_user) {
					//	вставка новых записей о людях в тело таблицы
					$('.table tbody').append('<tr>' +
						'<td>' + data.id_user[i] + '</td>' +
						'<td><img src="s3/img/' + data.photo[i] + '" style="width:200px;"></td>' +
						'<td>' + data.last_name[i] + '</td>' +
						'<td>' + data.first_name[i] + '</td>' +
						'<td>' + data.mid_name[i] + '</td>' +
						'<td>' + data.know[i] + '</td>' +
						'<td>' + data.skill[i] + '</td>' +
						'<td>' + data.hobby[i] + '</td>' +
						'</tr>');
				}
			}
		}
	};

	$.ajax({
		method: "POST",
		url: "system.php",
		dataType: 'json',
		data: { action: 'get_all_data' }
	})
		.done(function (data) { return clb(data); })
		.fail(function (jqXHR, textStatus, errorThrown) { alert('Не удалось получить данные... ', jqXHR, textStatus, errorThrown); });
}

//	после полной загрузки страницы можно начинать работу с базой и прочим
$(document).ready(function () {
	//	первичная загрузка данных из базы
	refresh_table();

	//	нажатие на кнопку добавления человека
	$('#form').unbind().submit(function (e) {
		//	чтобы не перезагружалась страница
		e.preventDefault();

		//	функция загрузки данных на сервер
		var upload_data = function (photo) {
			var to_send = {
				action: 'add_new_user',
				last_name: $('#new_user_last_name').val().trim(),
				first_name: $('#new_user_first_name').val().trim(),
				mid_name: $('#new_user_mid_name').val().trim(),
				know: $('#new_user_know').val().trim(),
				skill: $('#new_user_skill').val().trim(),
				hobby: $('#new_user_hobby').val().trim(),
				photo: photo
			};

			console.log('add_new_user to_send:', to_send);
			$.ajax({
				method: "POST",
				url: "system.php",
				dataType: 'json',
				data: to_send
				// cache: false,
				// processData:false
			})
			.done(function (msg) {
				//	очистка полей ввода
				$('#form')[0].reset();
				refresh_table();
			})
			.fail(function( jqXHR, textStatus, errorThrown ) { alert('proval...'); console.log('Не удалось получить данные... ', jqXHR, textStatus, errorThrown); });
		};

		//	обработка фотки
		//	для облегчения загрузки всех данных за один раз фото преобразуется в текст base64
		var photo = '';
		photo = $('#new_user_photo')[0].files[0];
		var reader = new FileReader();

		//	после полной обработки файла можно выхывать функцию загрузки
		reader.onloadend = function () {
			upload_data(reader.result);
		}

		//	если фото есть, то его надо присовокупить
		if (photo) {
			reader.readAsDataURL(photo);
		}
		//	иначео оставить поле пустым
		else {
			upload_data('');
		}
	});
});
