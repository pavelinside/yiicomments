<?php
/* @var $this yii\web\View */
?>
<style>
html, body {
    /*height: 1200px;*/
    padding: 0px;
    margin: 0px;
    overflow: hidden;
    font-size: 14px;
}
.wrap {
    min-height: 100%;
    height: auto;
    margin: 0 auto -60px;
    padding: 0 0 60px;
}
.wrap > .container {
    width:100%;
    padding: 0;
}
.gantt_message_area {
    top: 50px !important;
}
.gantt-messages {

}
.gantt-messages > .gantt_message {

}
.gantt-selected-info {

}
.gantt-selected-info h2 {

}
.select-task-prompt h2 {

}

.gantt-dropdown{
    position:absolute;
    top:0;
    left:0;
    width: 20px;
    height:100%;
    z-index:2;
    border-left:1px solid #cecece
}
.gantt-dropdown:hover{
    background: rgba(0,0,0, 0.1);
}
#gantt_dropdown{
    font-family: Arial, Helvetica, sans-serif;
    line-height:25px;
    position:absolute;
    display:none;
    border:1px solid #cecece;
    background: #fff;
    padding:10px;
    z-index: 10;
}
#gantt_dropdown input{
    margin: 0 5px;
}
#gantt_dropdown label{
    display:inline-block;
    width:100%;
    min-width: 120px;
    height: 25px;
}
</style>

<link rel="stylesheet" href="/js/dhtmlx-gantt/codebase/dhtmlxgantt.css?v=7.1.12">
<link rel="stylesheet" href="/js/dhtmlx-gantt/codebase/skins/dhtmlxgantt_meadow.css?v=7.1.12">
<script src="/js/dhtmlx-gantt/codebase/dhtmlxgantt.js?v=7.1.12"></script>
<!-- <script src="/js/dhtmlx-gantt/samples/common/testdata.js?v=7.1.12"></script> -->


<div class="wrap">
    <div id="calendarFilters" class="col-md-12">
            &nbsp;<select id="orderTeam" name="orderTeam[]" tabindex="-1">
<option value="2">Бригада Литвиненко Е.П.</option>
<option value="5" selected="">Бригада Бесхлебнов/Шевчук</option>
<option value="7">Бригада Страшков А.Ю.</option>
<option value="10">Сергеев К</option>
<option value="12">Бригада строителей Щырба Ю.А.</option>
<option value="16">Бригада Кавчак-Сердюк</option>
<option value="17">Бригада Христин</option>
<option value="20">Бригада Толстошеин-Остратенко</option>
<option value="23">Резерв</option>
<option value="26">Бригада Петрейко М.И.</option>
<option value="28">Бригада Сидоренко Р.</option>
<option value="30">Бригада Шумиловский М.</option>
<option value="33">Мамич А</option>
<option value="34">Бригада Фидирчак</option>
</select>
            <select id="orderTypes" name="orderTypes[]" tabindex="-1">
<option value="examination">Экспертиза</option>
<option value="pulling">Затяжка</option>
<option value="connection">Подключение</option>
<option value="reconnection">Повторное включение</option>
<option value="services">Услуги</option>
<option value="repair">Ремонт</option>
<option value="laying">Прокладка магистрали</option>
<option value="equipment">Оборудование</option>
<option value="office">Работа в офисе</option>
<option value="node">Работа на узле</option>
<option value="maintenance">ТО узлов</option>
<option value="posting">Расклейка</option>
<option value="instructions">Поручения</option>
</select>
            <select id="orderStates" name="orderStates[]" tabindex="-1">
<option value="pending">Ожидает</option>
<option value="postpone">Отложен</option>
<option value="executing">Выполняется</option>
<option value="done">Выполнен</option>
<option value="canceled">Отмена</option>
<option value="problem">Проблемный</option>
<option value="closed">Закрыт</option>
<option value="deleted">Удален</option>
</select>
         <input name="orderNum" value="" placeholder="Номер" title="Номер наряда" type="number" min="1" step="1" style="width:100px">
        <input type="text" name="orderAddress" style="width:auto" value="" placeholder="Адрес">
        <input type="date" name="start" value="2022-07-27" style="width:auto">

        <button class="btn btn-success">Применить</button>
        <span id="clearFilters" class="clearFilters" title="Сбросить фильтры">&nbsp;X&nbsp;</span>
    </div>

    <div id="calendar" style='width:100%; height:1000px;'></div>
    <div id="gantt_dropdown">
        <h2>Dropdown here</h2>
    </div>

</div>

<script>
  gantt.serverList("type", [
    {key: "examination", label: "Экспертиза"},
    {key: "pulling", label: "Затяжка"},
    {key: "connection", label: "Подключение"},
    {key: "reconnection", label: "Повторное включение"},
    {key: "services", label: "Услуги"},
    {key: "repair", label: "Ремонт"},
    {key: "laying", label: "Прокладка магистрали"},
    {key: "equipment", label: "Оборудование"},
    {key: "office", label: "Работа в офисе"},
    {key: "node", label: "Работа на узле"},
    {key: "maintenance", label: "ТО узлов"},
    {key: "posting", label: "Расклейка"},
    {key: "instructions", label: "Поручения"},
  ]);
  gantt.serverList("state", [
    {key: "pending", label: "Ожидает"},
    {key: "postpone", label: "Отложен"},
    {key: "executing", label: "Выполняется"},
    {key: "done", label: "Выполнен"},
    {key: "canceled", label: "Отмена"},
    {key: "problem", label: "Проблемный"},
    {key: "closed", label: "Закрыт"},
    {key: "deleted", label: "Удален"},
  ]);
  gantt.serverList("team", [
    {key: "2", label: "Литвиненко Е.П."},
    {key: "5", label: "Бесхлебнов/Шевчук"},
    {key: "7", label: "Страшков А.Ю."},
    {key: "10", label: "Сергеев К"},
    {key: "12", label: "Щырба Ю.А."},
    {key: "16", label: "Кавчак-Сердюк"},
    {key: "17", label: "Христин"},
    {key: "20", label: "Толстошеин-Остратенко"},
    {key: "23", label: "Резерв"},
    {key: "26", label: "Петрейко М.И."},
    {key: "28", label: "Сидоренко Р."},
    {key: "30", label: "Шумиловский М."},
    {key: "33", label: "Мамич А"},
    {key: "34", label: "Фидирчак"}
  ]);
  var stateEditor = {type: "select", map_to: "state", options:gantt.serverList("state")};
  function stateLabel(task){
    var value = task.state;
    var list = gantt.serverList("state");
    for(var i = 0; i < list.length; i++){
      if(list[i].key == value){
        return list[i].label;
      }
    }
    return "";
  }
  function teamLabel(task){
    var value = task.team;
    var list = gantt.serverList("team");
    for(var i = 0; i < list.length; i++){
      if(list[i].key == value){
        return list[i].label;
      }
    }
    return "";
  }


function ganntCalendar(){

}
ganntCalendar.columns = [
  {name:"text",
    label:"Исполнитель",
    tree:true, resize: true, hide: false, min_width: 222, width:'*',
    onrender: function(task, node) {
      //console.log(node);
      if(task.parent){
        node.setAttribute("title", task.user);
        node.innerHTML = task.user;
      } else {
        //node.style.backgroundColor = task.teamColor;
        node.style.color = task.teamColor;
        node.style.width = "100%";
      }
    }
  },

  {name:"type", label:"Тип", align: "center", width: 45,
    onrender: function(task, node) {
      node.setAttribute("title", task.type);
    }
  },
  {name:"state", label:"Статус", align: "center", template: stateLabel, width: 45,
    onrender: function(task, node) {
      node.setAttribute("title", task.state);
    }
  },
  {name:"building", label:"Адрес",   align: "left", min_width: 220, width: '*',
    onrender: function(task, node) {
      node.setAttribute("title", $(task.building).text());
    }
  }
];
ganntCalendar.columnsCreateConfig = function(selectedColumns){
  var newColumns = [];
  ganntCalendar.columns.forEach(function(column){
    if(selectedColumns[column.name]){
      newColumns.push(column);
    }
  });
  return newColumns;
};
/**
 * select настройка видимости колонок
 */
ganntCalendar.dropdownColumnsInit = function(){
  const filterContainer = document.getElementById('calendarFilters');
  if(!filterContainer){
    return;
  }
  const dropdownColumns = filterContainer.querySelector('calendarFilters');
  if(dropdownColumns){
    return;
  }
  filterContainer.insertAdjacentHTML('beforeend', '<div id="dropdownColumns" class="gantt-dropdown" onclick="ganntCalendar.dropdownColumnsShow(this)">&#9660;</div>');

  window.addEventListener("click", function(event){
    if(!event.target.closest("#gantt_dropdown") && !ganntCalendar.dropdownColumnsGetNode().keep){
      ganntCalendar.dropdownColumnsHide();
    }
  });
};
ganntCalendar.dropdownColumnsGetNode = function(){
  return document.querySelector("#gantt_dropdown");
};
/**
 * настройка видимости колонок - получить выбранные
 * @param node
 * @returns {{}}
 */
ganntCalendar.dropdownColumnsGetSelection = function(node){
  var selectedColumns = node.querySelectorAll(":checked");
  var checkedColumns = {};
  selectedColumns.forEach(function(node){
    checkedColumns[node.name] = true;
  });
  return checkedColumns;
};
/**
 * настройка видимости колонок - отобразить текущие колонки
 * @param node
 */
ganntCalendar.dropdownColumnsFill = function(node){
  var visibleColumns = {};
  gantt.config.columns.forEach(function(col){
    visibleColumns[col.name] = true;
  });
  var lines = [];
  ganntCalendar.columns.forEach(function(col){
    var checked = visibleColumns[col.name] ? "checked" : "";
    lines.push("<label><input type='checkbox' name='"+col.name+"' "+checked+">" + col.label + "</label>");
  });
  node.innerHTML = lines.join("<br>");
};
/**
 * настройка видимости колонок - отобразить выбор колонок
 * @param node
 */
ganntCalendar.dropdownColumnsShow = function(node){
  var position = node.getBoundingClientRect();
  var dropDown = ganntCalendar.dropdownColumnsGetNode();
  dropDown.style.top = position.bottom + "px";
  dropDown.style.left = position.left + "px";
  dropDown.style.display = "block";
  ganntCalendar.dropdownColumnsFill(dropDown);

  dropDown.onchange = function(){
    var selection = ganntCalendar.dropdownColumnsGetSelection(dropDown);
    gantt.config.columns = ganntCalendar.columnsCreateConfig(selection);
    // gantt.config.columns[0].width = 300; // rerender
    gantt.render();
  };

  dropDown.keep = true;
  setTimeout(function(){
    dropDown.keep = false;
  });
};
/**
 * настройка видимости колонок - скрыть выбор колонок
 * @param node
 */
ganntCalendar.dropdownColumnsHide = function(node){
  var dropDown = ganntCalendar.dropdownColumnsGetNode();
  dropDown.style.display = "none";
};

/**
 * пример настройки редактирования для колонок
 */
ganntCalendar.exampleColumnsEditing = function(){
  var textEditor = {type: "text", map_to: "text"};
  var dateEditor = {type: "date", map_to: "start_date", min: new Date(2022, 08, 02, 08, 00, 00), max: new Date(2022, 08, 12, 23, 00, 00)};
  var durationEditor = {type: "number", map_to: "duration", min: 0, max: 100};
  var stateEditor = {type: "select", map_to: "state", options:gantt.serverList("state")};

  // пример - редактировать только дочерние колонки https://snippet.dhtmlx.com/5/545b55dc9
  // другие события "onBeforeSave" "onEditStart" "onSave" "onEditEnd"
  gantt.ext.inlineEditors.attachEvent("onBeforeEditStart", function(state){
    var task = gantt.getTask(state.id);
    if (task.parent) {
      return true;
    }
    return false;
  });

  // при настройке колонки указать editor
  // {name:"start_date", label:"Start time", editor: dateEditor },
  // {name:"duration", label:"Duration", editor: durationEditor },
  // {name:"state", label:"Статус", template: stateLabel, editor: stateEditor }
};

ganntCalendar.exampleScroll = function(){
  // scroll sets https://plnkr.co/edit/hQvYgbqGD69G0dd1
  // gantt.config.scale_height = 30*2;
  // gantt.config.min_column_width = 50;
  gantt.config.layout = {
    css: "gantt_container",
    cols: [
      {
        width:400,
        min_width: 300,
        rows:[
          {view: "grid", scrollX: "gridScroll", scrollable: true, scrollY: "scrollVer"},
          {view: "scrollbar", id: "gridScroll", group:"horizontal"}
        ]
      },
      {resizer: true, width: 1},
      {
        rows:[
          {view: "timeline", scrollX: "scrollHor", scrollY: "scrollVer"},
          {view: "scrollbar", id: "scrollHor", group:"horizontal"}
        ]
      },
      {view: "scrollbar", id: "scrollVer"}
    ]
  };
};

ganntCalendar.exampleEvents = function(){
  gantt.attachEvent("onParse", function() {
    gantt.eachTask(function(task) {
      //task.user = Math.round(Math.random()*3);
    });
  });

  gantt.attachEvent('onTaskSelected', (id) => {
    let task = gantt.getTask(id);
    console.log('task-selected', task);
  });

  gantt.attachEvent('onTaskIdChange', (id, new_id) => {
    if (gantt.getSelectedId() == new_id) {
      let task = gantt.getTask(new_id);
      console.log('onTaskIdChange', task);
    }
  });

  gantt.attachEvent("onBeforeTaskAdd", function(id,task){
    //do not allow incorrect dates
    task.start_date = task.start_date || gantt.getTaskByIndex(0).start_date || new Date();
    task.end_date = task.end_date || gantt.getTaskByIndex(0).end_date || new Date();
    console.log("onBeforeTaskAdd", id, task);
    return false;
  });

  gantt.attachEvent("onTaskLoading", function (task) {
    // task.options_start = gantt.date.parseDate(task.options_start, "xml_date");
    return true;
  });

  gantt.attachEvent("onGanttScroll", function(left, top) {
    // any custom logic here
  });
};

ganntCalendar.exampleDataProcessor = function(){
  var dp = gantt.createDataProcessor({
    task: {
      create: function(data) {
        console.log("create", data);
        return Promise.reject({status: "qq", data: "value", data2: "value2"});
        return Promise.resolve({status: "ok", data: "value", data2: "value2"});
        //"action"=> "inserted",      "tid" => $task->id
        return gantt.ajax.post(server + "/" + entity, data);
      },
      update: function(data, id) {
        console.log("update", id);
        //return gantt.ajax.put(server + "/" + entity + "/" + id, data);
      },
      delete: function(id) {
        console.log("delete", id);
        //return gantt.ajax.del(server + "/" + entity + "/" + id);
      }
    },
    link: {
      create: function(data) {},
      update: function(data, id) {},
      delete: function(id) {}
    }
  });
  //dp.init(gantt);
};

ganntCalendar.exampleFunctions = function(){
  //gantt.getTaskRowNode(task.id);
  // `gantt.serialize()` or `gantt.getTaskByTime()` methods:

  gantt.message({
  	text: "<a target='_blank' href='https://docs.dhtmlx.com/gantt/desktop__server_side.html'>Require RESTful API</a><br> step-by-step tutorial <a target='_blank' href='https://docs.dhtmlx.com/gantt/desktop__howtostart_guides.html'>here </a>",
  	expire: 3000
  });
};



// настройка выбора колонок
ganntCalendar.dropdownColumnsInit();



function onReady(){

  // id: 10, text: "Сергеев К", start_date: "2022-09-02 10:00:00", duration: 3, progress: 0.4, open: true
  var data = [
    {
      id: 5,
      text: "Бесхлебнов/Шевчук",

      team: "<a href=\"/team/view?id=5\" style=\"color: #ff2424;\">Бесхлебнов/Шевчук</a>",
      teamid: 5,
      teamColor: '#ff2424',
      type: "",
      state: "",
      building: ""
    },
    {
      id: 90789,
      start_date: "2022-09-02 10:00:00",
      duration: 2,
      //progress: 0.4,
      parent: 5,

      user: "Бесхлебнов Артем, Шевчук Юрий",
      text: "<a class=\"tooltips-order tooltipstered\" href=\"/order/view?id=90789\" data-order=\"90789\"><img class=\"order-status-icons tooltipster\" src=\"/img/icons8-order-connection.png\" alt=\"\" title=\"Подключение\" data-type=\"connection\" style=\"width:18px;position:absolute;left:3px;\">#90789</a><img class=\"status-img\" src=\"/img/wing-agreed.png\" alt=\"\" style=\"width:25px;\">",
      team: "<a href=\"/team/view?id=5\" style=\"color: #ff2424;\">Бесхлебнов/Шевчук</a>",
      teamid: 5,
      type: "Подключение",
      state: "closed",
      building: "<a class=\"tooltips-address-info\" href=\"/apartment/view?id=35827\" data-type=\"apartment\" data-id=\"35827\"><i class=\"fa fa-building\"></i> Дружный 39, 9</a>",
      backgroundColor: "rgba(196, 242, 165, 0.8)"
    },
    {
      id: 91989,
      start_date: "2022-09-02 14:00:00",
      duration: 2,
      parent: 5,

      user: "Бесхлебнов Артем, Шевчук Юрий",
      text: "<a class=\"tooltips-order tooltipstered\" href=\"/order/view?id=91989\" data-order=\"91989\"><img class=\"order-status-icons tooltipster\" src=\"/img/icons8-order-node.png\" alt=\"\" title=\"Работа на узле\" data-type=\"node\" style=\"width:18px;position:absolute;left:3px;\">#91989</a>",
      team: "<a href=\"/team/view?id=5\" style=\"color: #ff2424;\">Бесхлебнов/Шевчук</a>",
      teamid: 5,
      type: "Работа на узле",
      state: "closed",
      building: "<a href=\"/box/view?id=986\">УА Царское село 2 OLT</a>",
      backgroundColor: "rgba(196, 242, 165, 0.8)"
    }
  ];

  gantt.i18n.setLocale("ru");
  //gantt.config.readonly = true;

  gantt.config.scales = [
    {unit: "day", step: 1, format: "%j %M %Y"}, //"%d.%m.%Y"
    {unit: "hour", step: 1, format: "%H:%i"}
  ];
  gantt.config.duration_unit = "hour";
  gantt.config.date_grid = "%Y-%m-%d %H:%i";
  gantt.config.date_format = "%Y-%m-%d %H:%i:%s";

  gantt.config.columns = ganntCalendar.columnsCreateConfig({
    text: true,
    type: false,
    state: false,
    building: true
  });

  //gantt.config.row_height = 50;
  // gantt.config.fit_tasks = true; // automatically extend the time scale in order to fit all displayed tasks
  gantt.config.open_tree_initially = true; // открывать дерево при старте

  // gantt.config.start_date = new Date(2022, 08, 02, 08, 00, 00);
  // gantt.config.end_date = new Date(2022, 08, 02, 23, 01, 00);
  gantt.init("calendar", new Date(2022, 08, 02, 08, 00, 00), new Date(2022, 08, 02, 23, 01, 00));

  gantt.parse({data: data,  links: []});

}

  // gantt.config.lightbox.sections = [
  // 	{name: "description", height: 70, map_to: "text", type: "textarea", focus: true},
  // 	{name: "time", type: "duration", map_to: "auto"}
  // ];

document.addEventListener('DOMContentLoaded', function(){
  onReady();
});
</script>
</body>
</html>