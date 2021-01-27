
///////////////////////////////////////////
//                                       //
//  Скрипт управляет диалогами и         //
//   хранящимся в куках списком алиасов  //
//                                       //
//  Алиасы городов из списка хранятся    //
//   в куках, сам список выводится       //
//   php исходя из содержимого кук       //
//                                       //
///////////////////////////////////////////

/* Опрос сервера на наличие алиаса */
function ask_alias_by_title(title)
{
    /* Создаем объект запроса */
    var xhr = new XMLHttpRequest();
    /* Кодируем title, так как utf-8 */
    var title_url = encodeURIComponent(title);
    /* Конфигурируем запрос, делаем блокирующим, чтобы
        не использовать onreadystatechange */
    xhr.open('GET', 'getalias.php?title='+title_url, false);
    /* Посылам запрос*/
    xhr.send();
    /* Если не 200 или признак отсутствия алиаса */
    if (xhr.status != 200)
    {
        /* То вернем error, как признак неудачи */
        return 'error';
    } 
    else
    {
        /* Иначе тут содержится title */
        return xhr.responseText;
    }
}

/* Основные обработчики событий с диалогами */
function add_city()
{
    /* Если в названии пусто - просим ввести город */
    if(dialog_add.add_title.value == '')
    {
        show_alert('Введите название!');
        return;
    }
    /* Опрашиваем getalias.php через xmlhttprequest */     
    get_alias(dialog_add.add_title, dialog_add.add_alias);
    /* Забираем алиас из диалога добавления алиаса */
    var alias = dialog_add.add_alias.value;
    /* Если xmlhttprequest завершился неудачей*/
    if(alias == 'error')
    {
        show_alert('Город не найден!');
        return;
    }
    /* Запрашиваем куку, где храним алиасы через разделитель | */
    var list = getCookie('list');
    /* Если в куках уже есть что-то по этому ключу */
    if(list!==undefined)
    {/* Если добавляемый алиас уже есть - уходим */
        if(list.indexOf(alias)+1)
        {
            show_alert('Город уже в списке!');
            return;
        }
        /* Иначе - добавляем его к строке через разделитель */
        list += '|' + alias;
    }
    else list = alias;
    /* Обновляем куку */
    date = new Date();
    date.setDate(date.getDate() + 7);
    setCookie('list', list, {expires: date});
    /* Перезагружаем страничку */
    document.location = document.location;
}

function delete_city()
{
    /* Берем алиас из диалога выбора действия */
    var alias = dialog_select.select_alias.value;
    /* Получаем куку с алиасами */
    var list = getCookie('list');
    /* Заменяем удаляемый алиас пустотой */
    list = list.replace(alias, '');
    /* Убираем двойной разделитель, если образовался */
    list = list.replace('||', '|');
    /* Обновляем куку */
    date = new Date();
    date.setDate(date.getDate() + 7);
    setCookie('list', list, {expires: date});
    /* Перезагружаем страничку */
    document.location = document.location;
}

function change_city()
{
    /* Если в названии пусто - просим ввести город */
    if(dialog_edit.edit_alias.title == '')
    {
        show_alert('Введите название!');
        return;
    }
    /* Опрашиваем getalias.php через xmlhttprequest */     
    get_alias(dialog_edit.edit_title, dialog_edit.edit_alias);
    /* Забираем алиас из диалога добавления алиаса */
    var alias = dialog_edit.edit_alias.value;
    /* Если xmlhttprequest завершился неудачей*/
    if(alias == 'error')
    {
        show_alert('Город не найден!');
        return;
    }
    /* Запрашиваем куку, где храним алиасы через разделитель | */
    var list = getCookie('list');
    /* Если новый алиас уже есть - уходим */
    if(list.indexOf(alias)+1)
    {
        show_alert('Город уже в списке!');
        return;
    }
    /* Берем заменяемый алиас из диалога редактирования алиаса */
    var alias_old = dialog_select.select_alias.value;
    /* Заменяем удаляемый алиас новым */
    list = list.replace(alias_old, alias);
    /* Обновляем куку */
    date = new Date();
    date.setDate(date.getDate() + 7);
    setCookie('list', list, {expires: date});
    /* Перезагружаем страничку */
    document.location = document.location;
}

function goto_city(alias){
    /* Запоминаем город */
    date = new Date();
    date.setDate(date.getDate() + 7);
    setCookie('show', alias, {expires: date});
    document.location = 'city.php';
}

function get_alias(elem, alias_elem){
    alias_elem.value = ask_alias_by_title(elem.value);
}

/* Функции для связи диалогов между собой */

function close_form(tform)
{
    tform.style.display = 'none';
}

function open_edit_form()
{
    dialog_edit.edit_title.is_need_clear = true;
    dialog_edit.style.display = 'block';
    close_form(dialog_select);
    dialog_edit.edit_title.value = dialog_select.select_title.value;
    dialog_edit.edit_alias.value = dialog_select.select_alias.value;
}

function open_delete_form()
{
    dialog_delete.style.display = 'block';
    close_form(dialog_select);
} 

function open_select_form(alias, title)
{
    dialog_select.style.display = 'block';
    close_form(dialog_edit);
    close_form(dialog_delete);
    dialog_select.select_title.value = title;
    dialog_select.select_alias.value = alias;
}

function open_add_form()
{
    dialog_add.add_title.is_need_clear = true;
    dialog_add.add_title.value = 'Город';
    dialog_add.style.display = 'block';
}

function show_alert(message)
{
    document.getElementById('message_text').innerHTML = message;
    dialog_alert.style.display = 'block';
}

function clear_once(elem)
{
    if(elem.is_need_clear)
    {
        elem.value = '';
        elem.is_need_clear = false;
    }
}
