// 
// $file1 = $srcImagePath;
// $file2 = $srcImagePath .'temp';
// $code = "$ghostScript -q -dNOPAUSE -dBATCH -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -sOutputFile=\"$file2\" \"$file1\" 2>&1";
// 
// $result1 = @exec($code);
// 
// $src_img=imagecreatefromjpeg($file2);
// 
// $deletefile = $file2;
//// Copyright (c) 2006 Chris Iufer & David Barshow (http://ecosmear.com/relay)

var FC = {
	URL: 'relay.php',
	TYPES: new Array('file','directory'),
	SELECTEDOBJECT: null,
	SHOWALL: false,
	SCRIPTSRC: location.href, 
	SEARCHOBJ: null,
	NEXTPATH: null,
	AJAXCALL: 0,
	UPLOADURL: 'upload.pl',
	DEBUG:false
};

// TODO: Directory and File should *ideally* be subclasses of the same class 
// since they share so much similar functionality.
var Directory = Class.create();
Directory.prototype = {
	
	initialize: function (path, name, flag, parentElement, virtual, scheme, displayname) {
		this.path = path; 		// "/filestore/folder2"
		this.type = 'directory';
		this.name = name; 		// "people"
		this.id = this.path; 	// "/filestore/folder2/people"
		this.flag = flag;
		this.virtual = virtual || false;
		this.open = false;
		this.selected = false;
		this.changed = false; 
		this.timer = null;
		this.interval = 1000;
		this.children = new Array(); // Only contains file elements when open!
		
		this.path == '' ? this.isRoot = true : this.isRoot = false;
		this.parentElement = parentElement;
		this.parentElement.object ? this.parentObject = this.parentElement.object :null;

		if(scheme) this.readonly = (scheme == 'read');
		else if(this.parentObject) this.readonly = this.parentObject.readonly;
		else this.readonly = true;
		
		displayname ? this.display = displayname: null;	
		this.createDirectory();
		FC.SHOWALL || this.virtual == "true" ? this.getContents() : this.virtual == "closed" ? this.virtual = true : null;
	},
	
	createDirectory: function () {
		this.element = document.createElement('div');
		this.link =    document.createElement('a');
		this.icon = document.createElement('img');
		this.handle = document.createElement('div');
		Element.addClassName(this.handle, 'handle');

		this.spinner = document.createElement('img');
		this.spinner.src = spinnerIcon;
		this.spinner.style.display="none";
		Element.addClassName(this.spinner, 'spinner');
		
		this.checkbox = document.createElement('input');
		this.checkbox.type = 'checkbox';
		
		this.span = document.createElement('span');
		this.icon.src = folderIcon;
		Element.addClassName(this.icon, 'icon');
		
		this.mark = document.createElement('img');
		this.virtual ? this.mark.src = vcollapsed : this.mark.src = collapsed;
		Element.addClassName(this.mark, 'mark');
		
		this.link.href = "javascript:go();";
		this.link.title = this.id;
		this.link.innerHTML = this.name;
		this.display ? this.link.innerHTML = this.display : null;
		
		Element.addClassName(this.link, this.flag);
		Element.addClassName(this.link, 'link');

		// Drop Down
		this.dropdown = document.createElement('select');
		this.dropdown.style.display = "none";
		this.dropdown.name = 'action'
		
		var selectAction = document.createElement('option');
		selectAction.text = '-select action-';
		selectAction.value = 'null';
		
		var newFolderOpt = document.createElement('option')
		newFolderOpt.text = 'New Folder'
		newFolderOpt.value = 'newFolder'
		
		var downloadFolderOpt = document.createElement('option')
		downloadFolderOpt.text = 'Add Folder to cart'
		downloadFolderOpt.value = 'downloadFolder'

		var renameFolderOpt = document.createElement('option')
		renameFolderOpt.text = 'Rename Folder'
		renameFolderOpt.value = 'renameFolder'
		
		var deleteFolderOpt = document.createElement('option')
		deleteFolderOpt.text = 'Delete Folder'
		deleteFolderOpt.value = 'deleteFolder'
		
		this.dropdown.options.add( selectAction );		
		this.dropdown.options.add( downloadFolderOpt )
		if(!this.readonly ) {
			this.dropdown.options.add( newFolderOpt );
			this.dropdown.options.add( renameFolderOpt );
			if(!this.virtual){
				this.dropdown.options.add( deleteFolderOpt )
			}		
		}
		Element.addClassName(this.dropdown, 'dropdown');
		
		// Note
		this.note = document.createElement('span');
		this.note.innerHTML = '(read-only)';
		Element.addClassName(this.note, 'note');

		// Events
		//
		// TODO: Attaching event handlers to every object is inefficient. 
		// Event delegation should be used instead.
		this.mark.onclick = this.openOrClose.bindAsEventListener(this);			
		this.icon.onmousedown = this.select.bindAsEventListener(this);
		this.icon.ondblclick = this.openOrClose.bindAsEventListener(this);
		this.link.onmousedown = this.select.bindAsEventListener(this);
		this.span.ondblclick = this.openOrClose.bindAsEventListener(this);
		var self = this;
		this.dropdown.onchange = function(){
			self.actionSelect(jQuery(this).val());
		}
		
		
		
		this.span.onmousedown = this.select.bindAsEventListener(this);
		this.checkbox.onchange = this.checkbox_handler.bindAsEventListener(this);
		
		this.link.onselectstart = function() {return false; }
		this.handle.appendChild(this.icon);
		this.handle.appendChild(this.link);
		this.span.appendChild(this.mark);
		this.span.appendChild(this.handle);			
		if(!this.isRoot && !this.readonly && !this.virtual){
			this.span.appendChild(this.checkbox);			
		};
		this.span.appendChild(this.dropdown)
		if(this.readonly){
			this.span.appendChild(this.note);
		};
		this.span.appendChild(this.spinner);
		this.element.appendChild(this.span);
				
		if (this.isRoot){
			this.span.style.display = "none";
		};

		this.element.id = "root" + this.id;
		this.element.object = this;
		
		Element.addClassName(this.element, this.type);
		if(this.virtual) {
			Element.addClassName(this.element, 'virtual');
			Element.addClassName(this.span, 'virtual');
		};
		if(this.readonly) {
			Element.addClassName(this.element, 'read');
			Element.addClassName(this.span, 'read');
		};
		this.parentElement.appendChild(this.element);	
		if(!this.isRoot && !this.readonly){
			if(!this.virtual) new Draggable(this.element.id, {revert:true, handle:'handle'});
			Droppables.add(this.element.id, { accept: FC.TYPES, hoverclass: 'hover', onDrop: this.moveTo.bind(this) });
			this.resetHierarchy();
		};
		
	 },
	
	// checkbox_stopEvent: function(event){
	// 	Event.stop(event);
	// },
	
	check:function(){
		this.checkbox.checked = true;
	},
	
	unCheck:function(){
		this.checkbox.checked = false;
	},
	
	checkbox_handler: function(){
		var checked = this.checkbox.checked;

		if(checked){
			addCheckedFile(this);
		}else{
			removeCheckedfile(this);
		}
	},
	

	// Adds all files, including those in subdirectories to download cart.
	addDl: function(folderPath){

		// Fetch will recursively call itself when it finds a directory.	
		function fetch(folderPath){
			var params = $H({ 
				relay: 'getFolder', 
				path: folderPath 
			});
		
			var ajax = new Ajax.Request(FC.URL, {
				
				onSuccess: function(response){
					var json_data = response.responseText;
					//eval("var jsonObject = ("+json_data+")");
					var jsonObject = jQuery.parseJSON(json_data)
					
					
					for(var i=0; i<jsonObject.bindings.length; i++){
						var item = jsonObject.bindings[i];
										
						if(item.type === 'file'){							
							cart.addSpecial(item.path, item.name);
						}else if(item.type === 'directory'){
							fetch(item.path);
						};
					};
				},
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { 
					showError(ER.ajax); 
				}
			});
		};
		
		fetch(folderPath);
	},
	
	actionSelect: function (action){

		switch(action){
			case 'newFolder':
				if(!this.readonly){
					newFolder(this);
				}else{
					alert('Folder is read-only!');
				};
				break;
			
			case 'downloadFolder':
				this.addDl(this.path);
				break;
				
			case 'renameFolder':
				if(!this.readonly){
					
					// Check file permissions
					// Note circumventing this will not allow editing a write-protected file 
					if( !fileWritable(this.path) ){
						alert('This folder is not writable.');
						return;
					}
					
					var newName = prompt('Rename folder ' + this.name + ' to:', this.name);

					if(newName && newName !== this.name){
						
						// Handle overwrite condition
						if( fileExists(this.parentElement.object.path + '/' + newName) ){
							var overWrite = confirm('A folder with that name already exists. Overwrite it?');
							if(!overWrite){
								return false;
							}
						}
						this.rename_handler('',cleanseFilename( newName) );
					}
					
				}else{
					alert('Folder is read-only!');
				}
				break;
				
			case 'deleteFolder':
				if(!this.readonly){
					
					if( !fileWritable(this.path) ){
						alert('This folder is not writable.');
						return;
					}
					
					
					if( !folderIsDeletable(this.path) ){
						alert('This folder contains write-protected folders or files.');
						return;
					}
					
					
					this.unlink();
				}else{
					alert('Folder is read-only!');
				}
				break;
		};
		
		this.resetDropDown();
	}, 
		
	resetDropDown: function (e){
		var options = this.dropdown.options;
		options[0].selected = true;
	},
	
	getContents: function () {
		if(this.opening) return false;
		this.opening = true;
		var params = $H({ relay: 'getFolder', path: this.path });
		this.showActivity();
		var ajax = new Ajax.Request(FC.URL, {
			onSuccess: this.getContents_handler.bind(this),
			method: 'post', 
			parameters: params.toQueryString(), 
			onFailure: function() { showError(ER.ajax); }
		});
	
	},

	getContents_handler: function (response) {		
		this.open = true;
		Element.addClassName(this.span, 'open');
		this.opening = false;
		this.virtual ? this.mark.src = vexpanded : this.mark.src = expanded;		
		this.hideActivity();
		var json_data = response.responseText;
		//eval("var jsonObject = ("+json_data+")");
		var jsonObject = jQuery.parseJSON(json_data)
		
		if(jsonObject.bindings.length == 0) {
			this.addBlank(); return true;
		};
		if(jsonObject.bindings[0].error){
			this.parentObject.update();
		};
		
		// Arrange so folders are always on top
		var dirs = [];
		var files= [];
				
		for(var i=0; i < jsonObject.bindings.length; i++){
			var object = jsonObject.bindings[i];

			if(object.type === 'directory'){
				dirs.push(object);
			}else{
				files.push(object);
			};
		};
		
		function compare(a,b) {
		  if (a.name < b.name)
		     return -1;
		  if (a.name > b.name)
		    return 1;
		  return 0;
		}
		
		files.sort(compare);
		dirs.sort(compare);
		
		for(var i=0; i<dirs.length; i++){
			this.addChild(dirs[i]);
		};
		for(var i=0; i<files.length; i++){
			this.addChild(files[i]);
		};
		
		
		
		if (this.andPick && i > 0){
			this.children[this.andPick].select();
		};
		if (FC.NEXTPATH && !this.isRoot){
			parsePath(FC.NEXTPATH);
		};
	},
	
	update: function(){
		if (this.open){
			var params = $H({ relay: 'getFolder', path: this.path });
			this.showActivity();
			var ajax = new Ajax.Request(FC.URL,{
				onSuccess : this.update_handler.bind(this),
				method: 'post',
				parameters: params.toQueryString(),
				onFailure: function(){ showError(ER.ajax); }
			});
		}else{
			this.getContents();
		};
	},

	update_handler: function(response) {
		this.hideActivity();
		this.open = true;
		var json_data = response.responseText; 
		//eval("var jsonObject = ("+json_data+")");
		var jsonObject = jQuery.parseJSON(json_data)
				
		if(jsonObject.bindings.length > 0){
			this.removeBlank();
		}else{
			this.addBlank();
		};
		
		for(var i=0; i < this.children.length; i++){
			var found = false;			
			for (var j=0; j < jsonObject.bindings.length; j++){
				if(this.children[i].id == jsonObject.bindings[j].id || this.children[i].id == jsonObject.bindings[j].path){
					found = true;
					break;
				};
			};

			if(found){
				if (this.children[i].name != jsonObject.bindings[j].name  || this.children[i].flag != jsonObject.bindings[j].flags && this.children[i].type == 'file') {
						this.children[i].name = jsonObject.bindings[j].name;
						this.children[i].flag = jsonObject.bindings[j].flags;
						this.children[i].refresh();
				};
				jsonObject.bindings.splice(j, 1);				
			}else{ 
				this.removeChild(this.children[i], i); 
				i--;
			};
		};
		
		for(var k=0; k < jsonObject.bindings.length; k++){
			
			 
			this.addChild(jsonObject.bindings[k]);
		};
		
		if (this.andPick && i > 0){
			this.children[this.andPick].select();
		};
		
		if(FC.NEXTPATH){
			parsePath(FC.NEXTPATH);
		};
	},
		
	openOrClose: function () {
		this.open ? this.clearContents() : this.getContents();
	},
	
	resetHierarchy: function(){
		if (this.parentObject.type == "directory" && this.parentObject.isRoot == false) {
			Droppables.remove(this.parentElement);
			Droppables.add(this.parentElement.id, { accept: FC.TYPES, hoverclass: 'hover', onDrop: this.parentObject.moveTo.bind(this.parentObject) });
			this.parentObject.resetHierarchy();
		};
	},
		
	clearContents: function(){				
		this.removeBlank();
		while(this.children.length > 0){
			(this.children[0].type == 'directory' && this.children[0].hasChildren()) ? this.children[0].clearContents() : this.removeChild(this.children[0], 0);
		};
		this.open = false;
		Element.removeClassName(this.span, 'open');
		this.virtual ? this.mark.src = vcollapsed : this.mark.src = collapsed;
	},

	removeChild: function (child, index) {
		if(!index) {
			for(var i=0; i< this.children.length; i++) {
				if(this.children[i] == child){ var index = i; break; }
			};
		};
		this.children.splice(index, 1);
		if(child.type == 'directory'){
			Droppables.remove(child.id);
		};
		Element.remove(child.element);
	},
	
	clear: function () { this.parentObject.removeChild(this); },

	addChild:  function (child) {
		
		
		if (child.type == 'file') {
			var newFile = new File(child.id, child.name, child.flags, this.element, child.date);
			
			if( isChecked(newFile) ){
				newFile.checkbox.checked = true;
			}

			this.children.push(newFile);
		}
		else if(child.type == 'directory') {
			var newDir = new Directory(child.path, child.name, child.flags, this.element, child.virtual, child.scheme, child.displayname);			

			if( isChecked(newDir) ){
				newDir.checkbox.checked = true;
			}

			this.children.push(newDir);
		}
		else return 0;
	 },
	
	moveTo: function (element) { 
		Element.removeClassName(this.element, 'hover');
		if (element.object.parentObject == this) { return false; }
		if ( element.object.type == 'directory' ) {
			
			// Check file permissions on me
			// Note circumventing this will not allow editing a write-protected file 
			if( !fileWritable(element.object.path) ){
				alert('This folder is not writable.');
				return;
			}
			
			// Handle overwrite condition
			if( fileExists(this.path + '/' + element.object.name) ){
				
				
				var overWrite = confirm('A folder with that name already exists there. Overwrite it?');
				if(!overWrite){
					return false;
				}
			}
			
			var params = $H({ 
				relay: 'folderMove', 
				name: element.object.name,
				path: element.object.parentObject.path, 
				where: this.path
			});
			
			
			FC.SEARCHOBJ = this;
			FC.NEXTPATH = '/'+ element.object.name; 
			
			element.object.clearContents();	
			element.object.clear();
			var ajax = new Ajax.Request(FC.URL, {
				onSuccess: this.update.bind(this), 
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			});
		// Move file
		} else { 
			
			
			// Handle overwrite condition
			if( fileExists(this.path + '/' + element.object.name) ){				
				var overWrite = confirm('A file with that name already exists there. Overwrite it?');
				if(!overWrite){
					return false;
				}
			}
			
			// Check file permissions on file to be moved
			if( !fileWritable(element.object.path + '/' + element.object.name) ){
				alert('This file is not writable.');
				return;
			}
			
			// Check file permissions on folder to moved out of
			if( !fileWritable(element.object.path) ){
				alert('This folder you\'re attempting to move out of is not writable.');
				return;
			}
			
			// Check file permissions on folder to be moved into
			if( !fileWritable(this.path) ){
				alert('This folder you\'re attempting to move into is not writable.');
				return;
			}
			
			
			var params = $H({ 
				relay: 'fileMove', 
				path: element.object.path,
				filename: element.object.name,
				id: element.object.id,
				where: this.path 
			});
			FC.SEARCHOBJ = this;
			FC.NEXTPATH = '/'+ element.object.name;
			if(!element.object.search) {
				element.object.clear();
				//delete element.object;
			}
			var ajax = new Ajax.Request(FC.URL,{
				onSuccess: this.update.bind(this),
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			});
		}		
						
	},
	
	select: function (event) {
				
		// Prevents dropdrop event from being mungled.
		if(event){		
			if(Element.hasClassName( $(event.target), 'dropdown') ){
				return;
			};
		};
		
		$('uploadPath').value = this.path;
		$('uploadstatus').innerHTML = "<em>Destination</em> "+this.path;
		// this.del.style.display = "block";
		// this.newFolder.style.display = "block";
		this.dropdown.style.display = "block";
		if(FC.SELECTEDOBJECT != null && FC.SELECTEDOBJECT != this) FC.SELECTEDOBJECT.deselect(); 
		window.onkeypress = this.select_handler.bindAsEventListener(this);
		FC.SELECTEDOBJECT = this;
				
		//Element.addClassName(this.element, 'selected');		
		Element.addClassName(this.span, 'selected');
		
		if ($('meta').prevElement != this.path ) this.getMeta();
		return false;
	},
	
	select_handler: function (event) {
		var charCode = (event.charCode) ? event.charCode : ((event.which) ? event.which : event.keyCode);
		switch(charCode) {
			//case Event.KEY_RETURN:
			//	this.showRename();
			//	break;
			case Event.KEY_DOWN:
				this.parentObject.nextChild(this);
				break;
			case Event.KEY_UP:
				this.parentObject.prevChild(this);
				break;
			case Event.KEY_RIGHT:
				if(this.open) this.children[0].select(); 
				else { this.openOrClose(); this.andPick = '0'; }
				break;
			case Event.KEY_LEFT:
				this.parentObject.select(this);
				break;
			}
  	},
	
	deselect: function () {
			this.timer = null;
			window.onkeypress = null;
			this.dropdown.style.display = "none";
			Element.removeClassName(this.span, 'selected');
			//Element.removeClassName(this.element, 'selected');		
			
			this.selected = false; 
			this.clearRename();
	},
	  
	getMeta: function () {
		$('meta').prevElement = this.path;
		var params = $H({ relay: 'getFolderMeta', path: this.path });
		var ajax = new Ajax.Request(FC.URL,{
			onLoading: showMetaSpinner(), 
			onSuccess: this.getMeta_handler.bind(this),
			method: 'post', 
			parameters: params.toQueryString(), 
			onFailure: function() { showError(ER.ajax); }
		});
	},
	
	getMeta_handler: function(response) {
		var json_data = response.responseText;		
		//eval("var jsonObject = ("+json_data+")");
		
		var jsonObject = jQuery.parseJSON(json_data)
		
		if (jsonObject.bindings.length >= 1) {
			var meta = { name: jsonObject.bindings[0].name, size: jsonObject.bindings[0].size, path: this.path};
			updateMeta(meta);
		}
		else updateMeta({ '<img src="/directory/sad.gif" />':'No Info to display'});	
	
	},
	
	nextChild: function(child) {
		var pos = this.checkIfChild(child);
		if(pos != this.children.length-1) this.children[pos+1].select();
		else if(!this.isRoot) this.parentObject.nextChild(this);
	},
	prevChild: function(child) {
		var pos = this.checkIfChild(child);
		if(pos != 0) this.children[pos-1].select();
		else if(pos == 0 && !this.isRoot) this.select();
	},
	
	checkIfChild: function (child) {
		if(this.hasChildren()){
		for (var i=0;i < this.children.length;i++){
			if(this.children[i].id == child.id) return i;
		} 
		return false;
		} else return false;
	},

	clearRename: function () {
		if(this.renameIsOpen) {
			Element.remove(this.newName);
			this.link.style.display = "block";
			this.renameIsOpen = false;
		}
	},
	
	rename_handler: function (event, direct) {		
		if(!direct) { var charCode = (event.charCode) ? event.charCode : ((event.which) ? event.which : event.keyCode); 
		if (charCode == Event.KEY_ESC) this.clearRename(); }
		if (charCode == Event.KEY_RETURN || direct) {
			var params = $H({ 
				relay : 'folderRename', 
				path  : this.parentObject.path, 
				name  : this.name, 
				newname: direct || this.newName.value });
			this.link.innerHTML = params.newname;
			this.clearRename();
			
			var ajax = new Ajax.Request(FC.URL, {
				onComplete: this.select.bind(this),
				onSuccess: this.parentObject.update.bind(this.parentObject),
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			});
		}		

	},
	
	showRename: function () {
		if(this.readonly) return false;
		if(this.virtual) return false;
		this.renameIsOpen = true;
		this.newName = document.createElement('input');
		this.newName.type = 'text';
		this.newName.name = this.id;
		this.newName.value = this.name;
		window.onkeypress = this.rename_handler.bindAsEventListener(this);
		Element.addClassName(this.newName, 'renamefield');
		this.link.style.display = "none";
		this.span.appendChild(this.newName);
		Field.select(this.newName);
	},
	
	showActivity: function () { 
		this.spinner.style.display = "block";
	},
	
	hideActivity: function () { 
		this.spinner.style.display = "none";
	},

	hasChildren: function () {
		if (this.children.length > 0) return true;
		else return false;
	},
	
	unlink: function () {
				
		if(this.readonly) return false;
		if(this.virtual) return false;

		if(confirm('Delete the folder '+this.name+ '?')) {			
			var params = $H({ relay: 'folderDelete', folder: this.path });
			this.parentObject.prevChild(this);
			var ajax = new Ajax.Request(FC.URL,{
				onComplete: this.parentObject.nextChild(this),
				onSuccess: this.clear.bind(this),
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			});
		}
	},
	
	addBlank: function() {
		if(this.blankisshowing) return false;
		this.blankisshowing = true;
		this.blank = document.createElement('div');
		this.blank.innerHTML = "This folder is empty";
		Element.addClassName(this.blank, 'blank');
		this.element.appendChild(this.blank);
	},
	
	removeBlank: function() {
		if(this.blankisshowing) {
			Element.remove(this.blank);
			this.blankisshowing = false;
		}
	}
	
};




var File = Class.create();
File.prototype = {
	
	initialize: function (id, name, flag, parentElement, date, optPath) {
		
		this.type = 'file';
		this.fileDate = date;
		this.name = name; 		// "AmeeDonavan.jpeg"
		this.id = id; 			// "_filestore_people_AmeeDonavan_jpeg"
		this.flag = flag;
		this.selected = false;
		this.timer = null;
		this.path = optPath || parentElement.object.path; // "/filestore/people" 
		this.interval = 1000;
		this.parentElement = parentElement;
		this.parentObject = parentElement.object;
		this.readonly = false; //this.parentObject.readonly;
		this.parentElement.id == 'searchresults' ? this.search = true : this.search = false;
		this.createFile();
	},
	
	createFile: function () { 
						
						
		this.element = document.createElement('div');
		this.span =    document.createElement('span');
		this.link =    document.createElement('a');
		this.icon = document.createElement('img');
		this.handle = document.createElement('div');
		Element.addClassName(this.handle, 'handle');
		
		this.checkbox = document.createElement('input');
		this.checkbox.type = 'checkbox';
		Element.addClassName(this.checkbox, 'fileCheckbox')
		
		this.flag != 'normal' ? this.icon.src = "images/"+this.flag+".png" : this.icon.src= fileIcon;
		Element.addClassName(this.icon, 'icon');
		this.link.title = this.id;
		this.link.innerHTML = this.name;
		Element.addClassName(this.link, this.flag);
		Element.addClassName(this.link, 'link');
		
		this.date = document.createElement('span');
		this.date.innerHTML = this.fileDate;
		Element.addClassName(this.date, 'date');
		
		// Drop Down
		this.dropdown = document.createElement('select');
		this.dropdown.style.display = "none";
		
		var selectAction = document.createElement('option');
		selectAction.text = '-select action-';
		selectAction.value = '';	
		
		var downloadFileOpt = document.createElement('option')
		downloadFileOpt.text = 'Download this file';
		downloadFileOpt.value = 'downloadFile';

		var addFiletoCartOpt = document.createElement('option')
		addFiletoCartOpt.text = 'Add file to cart';
		addFiletoCartOpt.value = 'addFiletoCart';
		
		var renameFileOpt = document.createElement('option')
		renameFileOpt.text = 'Rename File';
		renameFileOpt.value = 'renameFile';
		
		var deleteFileOpt = document.createElement('option')
		deleteFileOpt.text = 'Delete File';
		deleteFileOpt.value = 'deleteFile';
		
		this.dropdown.options.add( selectAction );		
		this.dropdown.options.add( downloadFileOpt );
		this.dropdown.options.add( addFiletoCartOpt );
		if(!this.readonly) {
			this.dropdown.options.add( renameFileOpt );
			this.dropdown.options.add( deleteFileOpt );
		}
		Element.addClassName(this.dropdown, 'dropdown');

		
		/*
			TODO: Attaching event handlers to every object is inefficient. 
			Event delegation should be used instead.
		*/
		this.span.onmousedown = this.select.bind(this);
		this.link.onmousedown = this.select.bindAsEventListener(this);
		this.icon.onmousedown = this.select.bind(this);
		this.link.ondblclick = this.download.bindAsEventListener(this);
		this.icon.ondblclick = this.download.bindAsEventListener(this);
		var self = this;
		this.dropdown.onchange = function(){
			self.actionSelect(jQuery(this).val());
		}
		this.checkbox.onchange = this.checkbox_handler.bindAsEventListener(this);
	
		if(!this.search){
			this.handle.appendChild(this.checkbox)
		}
		this.handle.appendChild(this.icon);
		this.handle.appendChild(this.link);
		this.span.appendChild(this.handle);
		this.span.appendChild(this.date);
		if(!this.search){
			this.span.appendChild(this.dropdown)
		}
		this.element.appendChild(this.span);
		
		this.search ? this.element.id = 'sid'+this.id : this.element.id = 'fid'+this.id;
		this.element.object = this;
		
		Element.addClassName(this.element, 'file');
		this.parentElement.appendChild(this.element);
		!this.readonly ? new Draggable(this.element.id, {revert:true, handle:'handle'}) : null;

	},
	check:function(){
		this.checkbox.checked = true;
	},
	unCheck:function(){
		this.checkbox.checked = false;
	},
	checkbox_handler: function(){
		var checked = this.checkbox.checked;
		
		if(checked){
			addCheckedFile(this);
		}else{
			removeCheckedfile(this);
		}
	},
	
	actionSelect: function (action){

		
		switch(action){
			case 'addFiletoCart':
				this.addDl();
				break;
			
			case 'downloadFile':
				location.href = FC.URL+'?relay=getFile&path=' + this.path + '/' + this.name
				// location.href = FC.URL+'?relay=getFilePackage&paths=' + this.path + '~' + this.name
				break;
				
			case 'renameFile':
				// DB readonly
				if(!this.readonly){
					
					// Check file permissions
					// Note circumventing this will not allow editing a write-protected file 
					if( !fileWritable(this.path + '/' + this.name) ){
						alert('This file is not writable.');
						return;
					}
					
						
					var newName = prompt('Rename folder ' + this.name + ' to:', this.name)
					if(newName && newName !== this.name){
					
						// Handle overwrite condition
						if( fileExists(this.path + '/' + newName) ){
							var overWrite = confirm('A file with that name already exists. Overwrite it?');
							if(!overWrite){
								return false;
							}
						}
						
						var params = $H({ 
							relay: 'fileRename', 
							
							path: this.path,
							filename: this.name,
							id: this.id,
							newName:newName
						});
						
						var self = this; // preserve "this" context
						var ajax = new Ajax.Request(FC.URL,{
							onLoading: showMetaSpinner(), 
							onSuccess: function(){
								self.name = newName;
								self.refresh();
								self.getMeta();
							},
							method: 'post', 
							parameters: params.toQueryString(), 
							onFailure: function() { showError(ER.ajax); }
						});
						
					}
				}
				break;
				
			case 'deleteFile':
			
				// Check file permissions
				// Note circumventing this will not allow editing a write-protected file 
				if( !fileWritable(this.path + '/' + this.name) ){
					alert('This file is not writable.');
					return;
				}
			
				// Database version of read-only. 
				if(!this.readonly){
					this.unlink();
				}else{
					alert('Folder is read-only!');
				}
				break;
		};
		
		this.resetDropDown();
	},
	
	resetDropDown: function (e){
		var options = this.dropdown.options;
		options[0].selected = true;
	},
	
	appearTools: function () { Effect.Appear(this.del.id); },
	fadeTools:   function () { this.del.style.display="none";  },
	
	addDl : function() { cart.add(this.element); },
	
	download: function () { location.href = FC.URL + '?relay=getFile&path=' + this.path +'/' + this.name; },
	
	refresh: function () {
		this.link.className = 'link';
		this.link.innerHTML = this.name;
		this.flag != '' ? this.icon.src = "images/"+this.flag+".png" : this.icon.src= fileIcon;
		
		Element.addClassName(this.link, this.flag);
	},
	
	select: function (ev) {		
		// Prevents dropdrop event from being mungled.
		if(ev){		
			if(Element.hasClassName( $(ev.target), 'dropdown') ){
				return;
			};
		};
		
		$('uploadPath').value = this.parentObject.path;
		$('uploadstatus').innerHTML = "<em>Destination</em> "+this.parentObject.path;
		this.dropdown.style.display = "block";
		// this.del.style.display = this.dl.style.display = "block";
		if(FC.SELECTEDOBJECT != null && FC.SELECTEDOBJECT != this) FC.SELECTEDOBJECT.deselect(); 
		window.onkeypress = this.select_handler.bindAsEventListener(this);
		FC.SELECTEDOBJECT = this;
		Element.addClassName(this.span, 'selected');

		if ($('meta').prevElement != this.id) { this.getMeta(); }
		return false;
	},

	select_handler: function (event) {
		var charCode = (event.charCode) ? event.charCode : ((event.which) ? event.which : event.keyCode); 
		//if (charCode == Event.KEY_RETURN) this.showRename();
		if (charCode == Event.KEY_DOWN) this.parentObject.nextChild(this); 
		else if (charCode == Event.KEY_UP) this.parentObject.prevChild(this); 
		else if (charCode == Event.KEY_LEFT) this.parentObject.select(this);				
  	},
	
	deselect: function () {
			this.timer = null;
			window.onkeypress = null;
			this.dropdown.style.display = "none";
			
			// this.del.style.display = this.dl.style.display ="none";
			Element.removeClassName(this.span, 'selected');
			this.selected = false; 
			this.clearRename();
	},
	
	getMeta: function () {
				
				
		$('meta').prevElement = this.id;
	
		var path = this.path; //this.parentObject.path;
		var filename = this.name;
		
		var params = $H({ 
			relay: 'getMeta', 
			path: path, 
			filename: filename,
			id: this.id
		});
		var ajax = new Ajax.Request(FC.URL,{
			onLoading: showMetaSpinner(), 
			onSuccess: this.getMeta_handler.bind(this),
			method: 'post', 
			parameters: params.toQueryString(), 
			onFailure: function() { showError(ER.ajax); }
		});
	},
	
	getMeta_handler: function (response) {
		var json_data = response.responseText;		
		//eval("var jsonObject = ("+json_data+")");
		var jsonObject = jQuery.parseJSON(json_data);
		var meta = {	edit		: jsonObject.bindings[0].edit,
						filename  : jsonObject.bindings[1].filename,
						date		: jsonObject.bindings[1].date,
						flag		: jsonObject.bindings[1].flag,
						type		: jsonObject.bindings[1].type || 'Document', //
						description: jsonObject.bindings[1].description,
						size		: jsonObject.bindings[1].size,
						file		: true,
						id			: this.id,
						image		: jsonObject.bindings[1].image,
						path		: jsonObject.bindings[1].path
					};
					
		this.flag = meta.flag;
		this.refresh()
			
		updateMeta(meta);
		
		
	},
	
	clear: function () {
		for (var i=0;i<this.parentObject.children.length;i++){
			if (this.parentObject.children[i] == this) {
				this.parentObject.children.splice(i,1);
				break;
			}
		}
		Element.remove(this.element);
	},
	
	unlink: function () {
		if(this.readonly) return false;
		
		if(confirm('Delete the file '+this.name+ '?')) {
		
			var params = $H({ 
				relay: 'fileDelete', 
				path: this.path,
				filename: this.name,
				id: this.id
			});
			var ajax = new Ajax.Request(FC.URL,{
				onComplete: this.parentObject.nextChild(this),
				onSuccess: this.clear.bind(this),
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			}); 
		}
	},
	
	clearRename: function () {
		if(this.renameIsOpen) {
			this.newName.style.display="none";
			Element.remove(this.newName);
			this.link.style.display = "block";
			this.renameIsOpen = false;		
			this.getMeta();
		}
	},
	
	rename_handler: function (event) {
		var charCode = (event.charCode) ? event.charCode : ((event.which) ? event.which : event.keyCode); 
		if (charCode == Event.KEY_ESC) this.clearRename();
		if (charCode == Event.KEY_RETURN) {
			var params = $H({
				relay : 'fileRename',
				path: this.path,
				filename: this.name,
				id: this.id
			});
			this.link.innerHTML = this.newName.value;
			this.name = this.newName.value;
			var ajax = new Ajax.Request(FC.URL, {
				onComplete: this.clearRename.bind(this),
				onSuccess: this.refresh.bind(this),
				method: 'post', 
				parameters: params.toQueryString(), 
				onFailure: function() { showError(ER.ajax); }
			});
		}		

	},
	
	showRename: function () {
		if(this.readonly) return false;
		this.renameIsOpen = true;
		this.newName = document.createElement('input');
		this.newName.type = 'text';
		this.newName.size = '40';
		this.newName.name = this.id;
		this.newName.value = this.name;
		window.onkeypress = this.rename_handler.bindAsEventListener(this);
		Element.addClassName(this.newName, 'renamefield');
		this.link.style.display = "none";
		this.span.appendChild(this.newName);
		this.newName.focus();
		this.newName.select();
		
	},
	
	update: function () {
		this.parentObject.update();
	}
};


function folderIsDeletable(path){
	var params = {  
		relay: 'folderIsDeletable', 
		path: path
	};
	
	// Prototype sucks. No async ajax.
	var response = jQuery.ajax({
		url: FC.URL,
		type: "POST",
		data: (params),
		dataType: "json",
		async: false
	}).responseText;
	
	try{
		var response = jQuery.parseJSON(response).bindings[0];
	}catch(e){
		alert('There seems to be a problem with the JSON response from the server.');
	}
	
	return response.writable;
}


function fileWritable(path){
	var params = {  
		relay: 'fileIsWritable', 
		path: path
	};
	
	// Prototype sucks. No async ajax.
	var response = jQuery.ajax({
		url: FC.URL,
		type: "POST",
		data: (params),
		dataType: "json",
		async: false
	}).responseText;
	
	try{
		var response = jQuery.parseJSON(response).bindings[0];
	}catch(e){
		alert('There seems to be a problem with the JSON response from the server.');
	}

	return response.writable;
}


// Synchronous ajax call to determine if a path already exists on the server.
function fileExists(path){
	var params = { 
		relay: 'fileExists', 
		path: path
	};
	
	// Prototype sucks. No async ajax.
	var response = jQuery.ajax({
		url: FC.URL,
		type: "POST",
		data: (params),
		dataType: "json",
		async: false

	}).responseText;
	
	return jQuery.parseJSON(response).bindings[0].exists;
}



// NON OBJECT METHODS
// ===========================================================================
 
function cleanseFilename(string){
	return string.replace(/['";{}]/g,"");
}

updateMeta = function (meta) {
	
	
	meta = $H(meta);
	$('meta').innerHTML = '';
	var path = meta.path.replace('/', ' /');
	if(meta.file) {
		var normalflag = hotflag = emergencyflag = '';
		
		switch(meta.flag) {
			case 'normal': normalflag = 'selected'; break;
			case 'hot': hotflag = 'selected'; break;
			case 'emergency': emergencyflag = 'selected'; break;
		}
		
		var metaFlags = '<option label="Normal" value="" '+normalflag+' >Normal</option><option label="Hot" value="hot" '+hotflag+' >Hot</option><option label="Emergency" value="emergency" '+emergencyflag+'>Emergency</option>';
		
		// Thumbnail
		if(meta.image) {
			
			$('meta').innerHTML += ''+
			'<div class="thumbbox">'+
				'<a href="'+FC.URL+'?relay=getFile&path='+meta.path+'/'+meta.filename+'" >'+
					'<img src="'+FC.URL+'?relay=getThumb&path='+meta.path+'&filename='+meta.filename+'" class="metaThumbnail" alt="" />'+
				'</a>'+
			'</div>';
		}
		
		$('meta').innerHTML += ' <table><tr><td class="l">Name</td><td><input type="text" name="filename" onfocus="window.onkeypress=\'null\'"; id="metaFilename" value="'+meta.filename+
			'" /></td></tr><tr><td class="l">Kind</td><td>'+meta.type+'</td></tr><td class="l">Size</td><td>'+meta.size+
			'</td></tr><tr><td class="l">Date</td><td>'+meta.date+
			'</td></tr>'+
			'<tr><td class="l">Where</td><td><div style="width:115px; overflow:hidden">'+path+'</div></td>'+
			'</tr><tr><td class="l">Flag</td><td><select id="metaFlag" name="metaFlag" id="metaFlag">'+metaFlags+'</select></td></tr>'+
			'<tr><tr><td class="l"><a href="#" onclick="saveMeta(); return false"><img src="'+saveIcon+'" alt="" /></a></td>'+
			'<td>'+
				'<textarea name="description" id="metaDesc">'+meta.description+'</textarea></td></tr></table>';
	}
	else if (FC.SELECTEDOBJECT.virtual) {
		$('meta').innerHTML = '<table><tr><td class="l">Name</td><td>'+meta.name+'</td></tr><tr><td class="l">Size</td><td>'+meta.size+'</td></tr></table>';
	}
	else {
		$('meta').innerHTML = '<table><tr><td class="l">Name</td><td><input type="text" id="folderMeta" name="folderMeta" value="'+meta.name+'" /></td></tr><tr><td class="l">Kind</td><td>Folder</td></tr><tr><td class="l">Size</td><td>'+meta.size+'</td></tr><tr><td class="l">Location</td><td><div style="width:115px; overflow:hidden"><a href="'+FC.SCRIPTSRC+'?path='+meta.path+'">'+path+'</a></div></td></tr><tr><td class="l">Label</td><td>Normal</td></tr><tr><td></td><td><a href="#" onclick="saveMeta(); return false"><img src="'+saveIcon+'" alt="" /></a></td></tr></table>';
	}
}

saveMeta = function () {
	if(FC.SELECTEDOBJECT.type == 'directory') {
		FC.SELECTEDOBJECT.rename_handler("", cleanseFilename( $('folderMeta').value ) );
		return false;
	}
		
	var id = FC.SELECTEDOBJECT.id; 
	var path = FC.SELECTEDOBJECT.path;
	var filename = FC.SELECTEDOBJECT.name;
	var description = $('metaDesc').value;
	var flags = $('metaFlag').options[$('metaFlag').selectedIndex].value;
		
	var params = $H({
		relay: 'setMeta', 
		path: path, 
		filename: filename,
		id: id,
		description: description,
		flags: flags
	});
	
	var ajax = new Ajax.Request(FC.URL, {
		onComplete: function() { $('metaSave').style.display = 'block'; Effect.Fade('metaSave', {duration:3}); },
		onSuccess: FC.SELECTEDOBJECT.refresh.bind(FC.SELECTEDOBJECT),
		method: 'post', 
		parameters: params.toQueryString(), 
		onFailure: function() { showError(ER.ajax); }
	});
	
	
	// For updating GUI I guess...
	var metaFilename = cleanseFilename( $('metaFilename').value );
	FC.SELECTEDOBJECT.name = metaFilename;
	FC.SELECTEDOBJECT.flag = flags;
	
	
}

/*
	SaveMeta needs to be refactored! saveMetaSpecial is in lue of that, and 
	follows the existing "convention" of cart.addSpecial() of creating a 
	duplicate function that accepts values vice being coupled to the UI.
*/
//function saveMetaSpecial(fileID, fileType, fileName, fileDescr, fileFlag){
function saveMetaSpecial(path, filename){
	
	fileName = cleanseFilename(fileName); 

	FC.SELECTEDOBJECT.name = fileName;
	FC.SELECTEDOBJECT.flag = fileFlag;
	
	var params = $H({
		relay: 'setMeta', 
		path: path, 
		filename: fileName

	});
	
	var ajax = new Ajax.Request(FC.URL, {
		onSuccess: FC.SELECTEDOBJECT.refresh.bind(FC.SELECTEDOBJECT),
		method: 'post', 
		parameters: params.toQueryString(), 
		onFailure: function() { 
			showError(ER.ajax); 
		}
	});
};


function showMetaSpinner () { $('meta').innerHTML = '<div style="border-bottom:0;" class="thumbbox"><img src="'+spinnerIcon+'" alt="" /></div>'; }

function parsePath(searchPath) {
	var path = searchPath.split('/');
	var children = $A(FC.SEARCHOBJ.children);
	var object = children.detect( function(value, index) {
			if (value.name == path[1]) return true;
			else return false;
		});
	if (path[2]) {
		FC.NEXTPATH = searchPath.replace('/'+path[1], '');
		if(object) {
			if (object.open) {
				FC.SEARCHOBJ.hideActivity();
				FC.SEARCHOBJ = object;
				parsePath(FC.NEXTPATH);
			}
			else {
				FC.SEARCHOBJ.hideActivity();
				object.update();
				FC.SEARCHOBJ = object; 
			}
		}
		else { 
			FC.NEXTPATH = null; 
			return showError(ER.parsePath);
		}
	}
	else {
		FC.SEARCHOBJ = null;
		FC.NEXTPATH = null;
		object.select();
	}
}

function getQuery(variable) {
 	var query = window.location.search.substring(1);
 	query = query.toQueryParams();
 	
 	if(query[variable]) {
 		FC.NEXTPATH = decodeURIComponent(query[variable]);
 		FC.SEARCHOBJ = root;
 	}
 	return true;
 }

function jumpTo(path) {
	path = decodeURI(path);
	FC.SEARCHOBJ = root;
	FC.NEXTPATH = path;
	parsePath(path);
}

function go() {}

// folder create method
// ===========================================================================
function newFolder(){
	if(FC.SELECTEDOBJECT == null) return false;
	if(FC.SELECTEDOBJECT.type == 'file') c = FC.SELECTEDOBJECT.parentObject;
	else c = FC.SELECTEDOBJECT;	

	var folderName = 'Untitled Folder';
	FC.NEXTPATH = '/'+folderName;
	FC.SEARCHOBJ = c;
	
	var params = $H({ relay: 'newFolder', name: folderName, path: c.path });
	var ajax = new Ajax.Request(FC.URL,{
		onSuccess: setTimeout("c.update()",100),
		method: 'post', 
		parameters: params.toQueryString(), 
		onFailure: function() { showError(ER.ajax); }
	});

}

uploadDestination = null

function uploadAuth() {

	
	if(QFiles.length == 0) return false;
	if(!FC.SELECTEDOBJECT) { return false;}
	
	if(FC.SELECTEDOBJECT.type == 'file') uploadDestination = FC.SELECTEDOBJECT.parentObject;
	else uploadDestination = FC.SELECTEDOBJECT;	
	
	var params = $H({ relay: 'uploadAuth', path: uploadDestination.path });
	var ajax = new Ajax.Request(FC.URL,{ onSuccess: uploadAuth_handler, method: 'post', parameters: params.toQueryString(), onFailure: function() { showError(ER.ajax); } });	
}

function uploadAuth_handler(response) {
	var json_data = response.responseText; 
//	eval("var jsonObject = ("+json_data+")");
	
	var jsonObject = jQuery.parseJSON(json_data)
	
	var auth = jsonObject.bindings[0];

	if(auth.auth == 'true') {
		sendUpload(auth.sessionid);
	}
	else showError(ER.upload);
}

function sendUpload(sid) {
		var uploadDumb = FC.UPLOADURL + '?'+ sid;
		$('uploadForm').action = uploadDumb;
		$('uploadForm').submit();
		
		$('uploadSubmit').src = uploadCancel;
		$('uploadSubmit').onclick = uploadStop;
		Element.toggle('uploadAdd');
		$('pgfg').style.width = "1px";	
		Effect.Appear('progress');
		window.setTimeout("uploadStatus()", 500);
}

function uploadStatus() {
		var params = $H({ relay: 'uploadSmart'});
		var ajax = new Ajax.Request(FC.URL, { onSuccess: uploadStatus_handler, method:'post', parameters: params.toQueryString(), onFailure: function() { showError(ER.ajax); } });		
}

uc = 0;
change = 0;
currentsize = 0;
destination = 0;
pginterval = 2000; 
refresh = 20;
pgwidth = 180;

function uploadStatus_handler(response) {
	var json_data = response.responseText; 
	//eval("var jsonObject = ("+json_data+")");
	var jsonObject = jQuery.parseJSON(json_data)
	
	var progress = jsonObject.bindings[0];

	if(progress.done == 'false') {
		window.setTimeout( "uploadStatus()", 1800);
		if(FC.PG) clearInterval(FC.PG);
		
		var p =  pgwidth * progress.percent;
		$('pgfg').style.width = p + 'px';
		currentsize = p;
		
		var pixels = progress.percentSec * pgwidth;
		change = pixels / refresh;
		destination = currentsize + pixels;
		
		FC.PG = setInterval("updatePgFg()", pginterval/refresh);
		
		$('pgsp').innerHTML = progress.speed;
		$('pgeta').innerHTML = progress.secondsLeft;
		
	}
	else uploadFinish();
}

function updatePgFg() {
	if (currentsize < destination) {
		uc++;		
		currentsize = currentsize + change;
		if(currentsize < pgwidth) {
			$('pgpc').innerHTML = parseInt((currentsize/pgwidth)*100) + "%";
			$('pgfg').style.width = currentsize + 'px';
		}
	}
}

function uploadFinish(stop) {
		//var params = $H({ relay: 'uploadSmart'});
		//var ajax = new Ajax.Request(FC.URL, { method:'post', parameters: params.toQueryString(), onFailure: function() { showError(ER.ajax); } });		

		change = 0;
		currentsize = 0;
		destination = 0;
		clearInterval(FC.PG);
		if(stop) $('pgpc').innerHTML = "Canceled";
		else $('pgpc').innerHTML = "100%";

		$('uploadSubmit').src = uploadBtn;
		$('uploadSubmit').onclick = uploadAuth;		
		Element.toggle('uploadAdd');		
		$('pgfg').style.width = pgwidth + "px";
		Effect.Fade('progress');
		uploadDestination.update();
		clearQ();
}

function uploadStop() {
	$('uploadiframe').src = "about:blank";
	uploadFinish(true);
}

function unlink() {
	FC.SELECTEDOBJECT ? FC.SELECTEDOBJECT.unlink() : null;
}
function download() {
	FC.SELECTEDOBJECT ? (FC.SELECTEDOBJECT.type == 'file' ? FC.SELECTEDOBJECT.download() : null) : null;
}

function updateAll(obj) {
	if(obj.open && obj.type=='directory'){
		for(i in obj.children) { updateAll(obj.children[i]); }
		obj.update();
	}
}
function openAll() {
	FC.SHOWALL = true; 
	root.clearContents(); 
	root.getContents(); 
	return false;
}
function closeAll() {
	FC.SHOWALL = false; 
	root.clearContents(); 
	root.getContents(); 
	return false;
}

QFiles = new Array();

var UploadManager = Class.create();
UploadManager.prototype = {
	initialize: function(element) {
		this.uploadQ = $('uploadFiles');
		this.buttons = $('uploadbuttons');
		this.size = 1;
		if(element) {
			this.input = $(element);
			this.id = element;
			this.input.value != '' ? this.addToQ() : null;
		}
		else this.createElement();
		this.input.onchange = this.addToQ.bind(this);
	},
	
	createElement: function() {
		this.id = 'upload'+ getRandom();
		this.input = document.createElement('input');
		this.input.type = 'file';
		this.input.name = 'file[]';
		this.input.size = this.size;
		Element.addClassName(this.input, 'fileupload');
		this.buttons.appendChild(this.input);

	},
	
	addToQ: function() {
		$('uploadQ').style.height = "auto";
		this.QPOS = QFiles.length;
		QFiles[this.QPOS] = this;
		var reg = /(.+(\\|\/))?(.*)/;
		var results = this.input.value.match(reg);
		this.filename = results[3];		
		this.row = document.createElement('tr');
		this.row.id = 'r'+getRandom();				
		this.name = document.createElement('td');
		this.name.innerHTML = '<div class="fileUp">'+this.filename+'</div>';		
		this.del = document.createElement('td');		
		this.link = document.createElement('img');
		this.link.onclick = this.clear.bind(this);
		this.link.src = removeIcon;		
		this.del.appendChild(this.link);
		this.row.appendChild(this.name);
		this.row.appendChild(this.del);
		this.uploadQ.appendChild(this.row);		
		Effect.Appear('uploadSubmit');
		next = new UploadManager();
	},
	
	clear: function() {
		Element.remove(this.row);
		Element.remove(this.input);
		QFiles.splice(this.QPOS, 1);
		if(QFiles.length == 0) {
			 Effect.Fade('uploadSubmit');
			 $('uploadQ').style.height = "28px";
		}
	},
	
	remove: function() {
		Element.remove(this.row);
		Element.remove(this.input);
	}
};

function getRandom() { return Math.round(Math.random()*1000); }
function clearQ() {
	for(var i=0;i < QFiles.length; i++){ QFiles[i].remove(); }

	Effect.Fade('uploadSubmit');
	$('uploadQ').style.height = "28px";
	QFiles = new Array();
}

var Cart = Class.create();
Cart.prototype = {
	initialize: function() {
		this.element = $('cart');
		this.children = new Array();
		this.confirm = $('emailconfirm');
		Droppables.add('cart', { accept: 'file', hoverclass: 'hover', onDrop: this.add.bind(this) });
	},
	
	toggleCart: function(){		
		Element.toggle('downloadcartclose');
		Element.toggle('downloadcart');
	},
	
	add: function (element) {
		if( !Element.visible('downloadcartclose') ){
			this.toggleCart();
		}
	
		var name = element.object.name;
		
		var filePath = element.object.path + '~' + element.object.name;
		
		for(var i=0; i < this.children.length; i++){
			if(filePath == this.children[i]) { return false; }
		}
	
		row = document.createElement('div');
		row.id = 'c'+filePath;
		row.innerHTML = '<div>'+name+'</div>';
		row.innerHTML += '<a href="#" onclick="cart.remove(\''+filePath+'\'); return false" class="remove"></a>';
	
		this.element.appendChild(row);
		this.children[this.children.length] = filePath;
		
	},
	addSpecial: function (path, name) {
				
		var e = { object: {path: path, name: name} };
		cart.add(e);
	},
			
	remove: function (fid) {
		for(var i=0; i < this.children.length; i++){
			if(fid == this.children[i]) { this.children.splice(i,1); Element.remove('c'+fid); break; }
		}
	},
	
	download: function() {
		if(this.children.length == 0 && FC.SELECTEDOBJECT == null) return false;
		if(this.children.length == 0 && FC.SELECTEDOBJECT) { FC.SELECTEDOBJECT.download(); return false; }
		var cartIDs = '';
		
		for(var i=0; i < this.children.length; i++) {
			if(this.children[i] != ''){
				cartIDs += this.children[i];
				if(i != this.children.length-1) cartIDs += ',';
			}
		}
		for(var i=0;i< this.children.length; i++){ 
			Element.remove('c'+this.children[i]);
		}
		this.children = new Array();

		if($('emailFormTo').value != '' && $('emailFormTo').value != 'Type email address') 
			this.email(cartIDs);
		else
			location.href = FC.URL+'?relay=getFilePackage&paths=' + cartIDs;
	},
	
	email: function(cartIDs) {
		var params = $H({
			relay: 'emailFilePackage', 
			to: $('emailFormTo').value, 
			from: $('emailFormFrom').value, 
			message: $('emailFormMessage').value,
			fileid: cartIDs
		});	
		var ajax = new Ajax.Request(FC.URL,{
			onSuccess: this.email_handler.bind(this),
			method: 'get', 
			parameters: params.toQueryString()
		});
	},
	
	email_handler: function() {
		this.hideEmail();
		Effect.Appear(this.confirm);
		setTimeout("Effect.Fade('emailconfirm');", 2000);
	},
	showEmail: function() {
		Effect.Appear('emailform');
	},
	hideEmail: function() {
		Effect.Fade('emailform');
	}
	
};

var ER = {
	auth: 'You don\'t have authorization to use this app. Please try <a href="login.htm">logging in</a> again',
	ajax: 'Unable to make a connection to the server',
	upload: 'Can\'t upload to this folder. You may not have write privileges',
	download: 'Your download cart is empty',
	parsePath: 'The file you are looking for is not there'
};

showError = function(text) {
	$('error').innerHTML = "<img src=\"/images/icons/exclamation.png\" /><p>" + text + "</p><a href=\"#\" class=\"close\" onclick=\"Effect.toggle('error', 'appear'); return false\" />close</a>";
	Effect.Appear('error');
	return false;
}

showLogin = function() {
	$('login').style.display = "block";
}

function userLogin(){
	var params = $H({ relay: 'userLogin', username: $('username').value, password: $('password').value });
	var ajax = new Ajax.Request(FC.URL,{
		onSuccess: userLogin_handler,
		method: 'post', 
		parameters: params.toQueryString()
	});
	return false;
}

function userLogin_handler(response){      
	var json_data = response.responseText;
	//eval("var jsonObject = ("+json_data+")");
	var jsonObject = jQuery.parseJSON(json_data)
	
	var status = jsonObject.bindings[0];
	if (status.login == 'true'){ 
		root.getContents(); 
		$('login').style.display="none";
	}
	else if ($('password').value != '') $('warning').style.display="block";
}

function submitenter(myfield,e) {
	var keycode;
	if (window.event) keycode = window.event.keyCode;
	else if (e) keycode = e.which;
	else return true;
	
	if (keycode == 13) {
	   userLogin();
	   return false;
	  }
	else return true;
}

search = null;
cart = null;
root = null;

// Mass Action
// ===========================================================================
var checkedFiles = []; // file & directory elements

function addCheckedFile(file){
	for(var i=0; i< checkedFiles.length; i++){
		if(checkedFiles[i].id === file.id){
			return;
		};
	};
	
	checkedFiles.push(file);
};

function removeCheckedfile(file){
	for(var i=0; i< checkedFiles.length; i++){
		if(checkedFiles[i].id === file.id){				
			checkedFiles.splice(i,1);
		};
	};
};

function isChecked(file){
	for(var i=0; i< checkedFiles.length; i++){
		if(checkedFiles[i].id === file.id){				
			return true;
		};
	};
	
	return false;
};

function unCheckAll(){
	for(var i=0; i< checkedFiles.length; i++){
		checkedFiles[i].unCheck();	
	};			
};

function massAction(){

	function massCleanUp(){
		unCheckAll();
		checkedFiles = [];	
	};
	
	function massAdd(){
		for(var i=0; i<checkedFiles.length; i++){					
			checkedFiles[i].addDl(checkedFiles[i].path);
		};
		massCleanUp();
	};
	
	function massDelete(){
		for(var i=0; i<checkedFiles.length; i++){			
			checkedFiles[i].unlink();
		};
		
		massCleanUp();
	};
	
	function massMove(){
		var selectionIndex = $('massMove').options.selectedIndex;
		var selectionValue = $('massMove').options[selectionIndex].value;
		
		
		
		massCleanUp();
		var options = $('massMove').options;
		options[0].selected = true;
	};
	
	$('massDelete').onclick = massDelete.bindAsEventListener(this);
	$('massAdd').onclick = massAdd.bindAsEventListener(this);
	// $('massMove').onchange = massMove.bindAsEventListener(this);
};
// ===========================================================================






function userLogin_handler_check(response){      
	var json_data = response.responseText;
	//eval("var jsonObject = ("+json_data+")");
	var jsonObject = jQuery.parseJSON(json_data)
	
	var status = jsonObject.bindings[0];
	if(status.login != 'true'){		
		document.location = "index.php";
	}
}

windowLoader = function () { 
	root = new Directory('', '', false, $('fileList'));
	
	root.getContents();
	getQuery('path');
	
	new UploadManager('fileUpload');
	cart = new Cart('cart');
	setInterval("updateAll(root)", 60000);
	search = new Search('searcharea');
	//Field.activate('searchbar');
	
	var ajax = new Ajax.Request('relay.php', {onSuccess: userLogin_handler_check, method: 'post', parameters: 'relay=checkLogin'});

	massAction();
}

window.onload = windowLoader;