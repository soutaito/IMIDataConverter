(function($){
	var vm = new Vue({
		el: '#mapping_area',
		data: {
			token:null,
			inspect:false,
			project:{},
			mapping:[],
			suggestion:[],
			searchList:[],
			icName:'',
			addRowIndex:null,
			setMapping:{
				index:null,
				targetName:'',
				targetType:'',
				targetTypeText:'',
				targetCells:'',
				propertyType:'',
				APIComponent:'',
				APIComponentURL:''
			},
			uriEdit:false,
			sheetData:[],
			activeCell:{
				set_header:[],
				set_idrule:[],
				set_mapcel:[]
			}
		},
		methods: {
			init:function(){
				var self = this;
				$.ajax({
					url: productPath + "api/project/json",
					method: 'GET',
					dataType: 'json'
				}).done(function(data){
					self.$set('project',data);
					self.$set('mapping',data['eg:property'] || []);
				});
				$.ajax({
					url: productPath + "api/project/sheetData",
					dataType: 'json'
				}).done(function(data){
					self.$set('sheetData',data);
				});
				$.ajax({
					url: productPath + "api/vocabulary/ic",
					dataType: 'json'
				}).done(function(data){
					self.$set('suggestion.ic', data);
				});

				self.$set('activeCell.set_header',[]);
				self.$set('activeCell.set_idrule',[]);
				self.$set('activeCell.set_mapcel',[]);
				self.$set('token',$('#_token').val());
			},
			handleSave:function(){
				var self = this;
				var token = self.$get('token');
				$.ajax({
					url: productPath + "project/mapping/save",
					method: 'POST',
					dataType: 'text',
					data:{
						project:self.project,
						mapping:JSON.stringify(self.mapping),
						_token:token
					}
				}).fail(function(e){
					$('.flash').html('<p class="error">' + e.responseText + '</p>');
					$('html, body').animate({scrollTop:0}, 'fast');
				}).done(function(data){
					location.href = productPath + "project/input";
				});
			},
			suggestionToggle:function(e){
				$('.dropdown-menu.parent').toggle();
			},
			listSuggestion:function(e){
				e.preventDefault();
				var self = this;
				var vocabulary = $(e.target).data('vocabulary');
				if(self.$get('suggestion.' + vocabulary) == undefined){
					$.ajax({
						url: productPath + "api/vocabulary/" + vocabulary ,
						dataType: 'json'
					}).done(function(data){
						self.$set('suggestion.' + vocabulary,data);
					});
				}
			},
			suggestionSet:function(list){
				var self = this;
				var rows = [];
				var val = list.$value.split(':');
				$.ajax({
					url: productPath + "api/vocabulary/" + val[0] + "/complexType/" + val[1],
					dataType: 'json'
				}).done(function(data){
					$.each(data.element,function(key,value){
						var obj = {};
						obj["eg:predicate"] = value.name;
						obj["eg:type"] = value.type;
						obj["level"] = 1;
						rows.push(obj);
					});
					self.$set('mapping',rows);
				});
				self.$set("project['eg:class']",list.$value);
				$('.dropdown-menu.parent').toggle();
			},
			propertyAddModalOpen:function(i){
				var index = i || null;
				var self = this;
				$.ajax({
					url: productPath + "api/vocabulary/searchlist",
					dataType: 'json'
				}).done(function(data){
					self.$set('searchList',data);
				});
				self.$set('icName','');
				self.$set('addRowIndex',index);
				$('#addRowModal').modal('show');
			},
			propertyAdd:function(){
				var icName = this.icName;
				var mapping = this.mapping;
				var addRowIndex = this.addRowIndex;
				addRowIndex++;
				if(addRowIndex){
					mapping.splice(addRowIndex,0,{
						"eg:predicate":icName,
						"eg:type":'',
						"level":mapping[addRowIndex-1]['level']+1,
						"eg:tree":mapping[addRowIndex-1]['eg:predicate'] + '-' + icName
					});
				}else{
					mapping.push({
						"eg:predicate":icName,
						"eg:type":'',
						"level":1
					});
				}
				this.$set('mapping',mapping);
				$('#addRowModal').modal('hide');
			},
			propertyDelete:function(index){
				var mapping = this.mapping;
				var level = mapping[index].level;
				mapping.splice(index,1);
				while(mapping[index] && mapping[index].level && mapping[index].level > level){
					mapping.splice(index,1);
				}
				this.$set('mapping',mapping);
			},
			propertyAddChild:function(index){
				var self = this;
				var mapping = self.mapping;
				var ic = mapping[index]["eg:predicate"];
				var level = mapping[index].level+1;
				$.ajax({
					url: productPath + "api/vocabulary/ic/element/"+ic,
					dataType: 'json'
				}).done(function(data){
					if(data.element){
						$.each(data.element,function(key,value){
							index++;
							var obj = {};
							obj["eg:predicate"] = value.name;
							obj["eg:type"] = value.type;
							obj["level"] = level;
							obj["eg:tree"] = ic + '-' + value.name;
							mapping.splice(index,0,obj);
						});
						self.$set('mapping',mapping);
					}
				});
			},
			hasChildProperty:function(index){
				return this.mapping[index]['eg:type'].indexOf('ic:') === 0;
			},
			hasChildDom:function(index){
				return this.mapping[index+1] && this.mapping[index].level < this.mapping[index+1].level ? true : false;
			},
			setMappingOpen:function(index){
				$('.modal_flash').html('');
				if(this.mapping[index]['eg:targetCells'] && this.mapping[index]['eg:targetCells'].substring(0,1) == 'c'){
					this.$set('activeCell.set_mapcel',[0,this.mapping[index]['eg:targetCells'].substring(1)]);
				}else if(this.mapping[index]['eg:targetCells'] && this.mapping[index]['eg:targetCells'].substring(0,1) == 'r'){
					this.$set('activeCell.set_mapcel',[this.mapping[index]['eg:targetCells'].substring(1),0]);
				}else{
					this.$set('activeCell.set_mapcel',[]);
				}
				var setMapping = {
					index:index,
					targetName:this.mapping[index]['eg:predicate'],
					targetCells:this.mapping[index]['eg:targetCells'] || null,
					targetType:this.mapping[index]['eg:targetType'] || 'column',
					targetTypeText:this.mapping[index]['eg:targetTypeText'] || null,
					propertyType:this.mapping[index]['eg:type'] || null,
					APIComponent:this.mapping[index]['eg:APIComponent'] || '',
					APIComponentURL:this.mapping[index]['eg:APIComponentURL'] || null
				};
				this.$set('setMapping',setMapping);
				$('#modalDefine').modal('show');
			},
			saveMapping:function(){
				var mapping = this.mapping;
				var setMapping = this.setMapping;
				var targetIndex = setMapping.index;
				var error = '';
				switch(setMapping.targetType){
					case 'column':
						if(!setMapping.targetCells){
							error += '列を指定してください。<br />';
						}
						break;
					case 'increment':
						break;
					case 'constant':
						if(!setMapping.targetTypeText){
							error += '固定値を入力してください。<br />';
						}
						break;
					case 'uri':
						if(!setMapping.targetTypeText || !this.isUri(setMapping.targetTypeText)){
							error += 'URIとして形式が正しくありません。<br />';
						}
						break;
					default :
						error += '値を指定してください。<br />';
						break;
				}
				if(setMapping.APIComponent === 'external' && (!setMapping.APIComponentURL || !this.isUri(setMapping.APIComponentURL))){
					error += '外部APIの値がURIとして正しくない形式です。<br />';
				}
				if(error !== ''){
					$('.modal_flash').html('<p class="error">' + error + '</p>');
					return false;
				}

				this.$set('mapping[' + targetIndex + "]['eg:targetCells']" ,this.setMapping.targetCells);
				this.$set('mapping[' + targetIndex + "]['eg:targetType']" ,this.setMapping.targetType);
				this.$set('mapping[' + targetIndex + "]['eg:targetTypeText']" ,this.setMapping.targetTypeText);
				this.$set('mapping[' + targetIndex + "]['eg:type']" ,this.setMapping.propertyType);
				this.$set('mapping[' + targetIndex + "]['eg:APIComponent']" ,this.setMapping.APIComponent);
				this.$set('mapping[' + targetIndex + "]['eg:APIComponentURL']" ,this.setMapping.APIComponentURL);
				this.$set('mapping[' + targetIndex + ']' ,mapping[targetIndex]);
				$('#modalDefine').modal('hide');
			},
			setDataPreviewActive:function(el){
				var $this = $(el);
				var dataClass = $this.data('class');
				var rowNum = $this.data('row');
				var colNum = $this.data('col');
				var activeCell = this.activeCell[dataClass] || [];
				if(dataClass == 'set_header'){
					if(this.project['eg:definitionType'] == 'row'){
						if(rowNum == activeCell[0]){
							var headerLabel = this.project['eg:headerLabel'];
							if($.inArray($this.text(), headerLabel ) === -1){
								headerLabel.push($this.text());
								this.$set("project['eg:headerLabel']",headerLabel);
							}
							return true;
						}
					}else if(this.project['eg:definitionType'] == 'column'){
						if(colNum == activeCell[1]) return true;
					}
					return false;
				}
				if(dataClass == 'set_idrule'){
					if(this.project['eg:subject']['eg:namingRule'] == 'row'){
						if(rowNum == activeCell[0]) return true;
					}else if(this.project['eg:subject']['eg:namingRule'] == 'column'){
						if(colNum == activeCell[1]) return true;
					}
					return false;
				}
				if(dataClass == 'set_mapcel'){
					if(this.project['eg:definitionType'] == 'column'){
						if(rowNum == activeCell[0]) return true;
					}else if(this.project['eg:definitionType'] == 'row'){
						if(colNum == activeCell[1]) return true;
					}
					return false;
				}
				return false;
			},
			selectIdCell:function(e){
				var self = this;
				var $this = $(e.target);
				var dataClass = $this.data('class');
				var rowNum = $this.closest('table').find('tr').index($this.closest('tr'));
				var colNum = $this.closest('tr').find('td').index($this);
				this.$set('activeCell.'+dataClass,[rowNum,colNum]);

				if(dataClass == 'set_header'){
					if(this.project['eg:definitionType'] == 'row'){
						self.$set("project['eg:headerIndex']",'r'+rowNum);
					}else if(this.project['eg:definitionType'] == 'column'){
						self.$set("project['eg:headerIndex']",'c'+colNum);
					}else{
					}
				}
				if(dataClass == 'set_idrule'){
					if(this.project['eg:subject']['eg:namingRule'] == 'column'){
						self.$set("project['eg:subject']['eg:targetCells']",'c'+colNum);
					}else if(this.project['eg:subject']['eg:namingRule'] == 'row'){
						self.$set("project['eg:subject']['eg:targetCells']",'r'+rowNum);
					}else{
						self.$set("project['eg:subject']['eg:targetCells']",null);
					}
				}
				if(dataClass == 'set_mapcel'){
					if(this.project['eg:definitionType'] == 'row'){
						self.$set('setMapping.targetCells','c'+colNum);
					}else if(this.project['eg:definitionType'] == 'column'){
						self.$set('setMapping.targetCells','r'+rowNum);
					}else{
					}
				}
			},
			toggleUriEdit:function(){
				this.$set('uriEdit',!this.$get('uriEdit'));
			},
			saveUri:function(){
				this.toggleUriEdit();
			},
			isUri:function(s){
				//厳密（RFC3986準拠）ではない
				var regex = new RegExp("^(http[s]?:\\/\\/(www\\.)?|ftp:\\/\\/(www\\.)?|www\\.){1}([0-9A-Za-z-\\.@:%_\+~#=]+)+((\\.[a-zA-Z]{2,3})+)(/(.)*)?(\\?(.)*)?");
				return regex.test(s);
			}
		},
		ready: function(){
			this.init();
		}
	});
})(jQuery);
