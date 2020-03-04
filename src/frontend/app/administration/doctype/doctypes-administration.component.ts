import { Component, OnInit, ViewChild, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService }        from '../../../service/header.service';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { AppService } from '../../../service/app.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "doctypes-administration.component.html",
    providers: [NotificationService, AppService]
})

export class DoctypesAdministrationComponent implements OnInit {

    @ViewChild('snav', { static: true }) public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2', { static: true }) public sidenavRight  : MatSidenav;

    dialogRef: MatDialogRef<any>;
    config: any = {};
    lang: any = LANG;

    doctypes: any[] = [];
    currentType: any = false;
    currentSecondLevel: any = false;
    currentFirstLevel: any = false;
    firstLevels: any = false;
    folderTypes: any = false;
    types: any = false;
    secondLevels: any = false;
    processModes: any = false;

    loading: boolean = false;
    creationMode: any = false;
    newSecondLevel: any = false;
    newFirstLevel: any = false;

    displayedColumns = ['label','use', 'mandatory', 'column'];
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        public dialog: MatDialog, 
        private headerService: HeaderService,
        public appService: AppService
    ) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.documentTypes);
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(this.sidenavRight);

        this.loading = true;

        this.http.get("../../rest/doctypes")
            .subscribe((data: any) => {
                this.doctypes = data['structure'];
                setTimeout(() => {
                    $j('#jstree').jstree({
                        "checkbox": {
                            "three_state": false //no cascade selection
                        },
                        'core': {
                            force_text : true,
                            'themes': {
                                'name': 'proton',
                                'responsive': true
                            },
                            'multiple': false,
                            'data': this.doctypes,
                            "check_callback": function (operation: any, node: any, node_parent: any, node_position: any, more: any) {
                                if (operation == 'move_node') {
                                    if(typeof more.ref == "undefined"){
                                        return true;
                                    }
                                    if(!isNaN(parseFloat(node.id)) && isFinite(node.id) && more.ref.id.indexOf("secondlevel_")==0){
                                        // Doctype in secondLevel
                                        if(more.ref.children.indexOf(node.id) >-1){
                                            // same secondLevel
                                            return false;
                                        } else {
                                            return true;
                                        }
                                    } else if(node.id.indexOf("secondlevel_")==0 && more.ref.id.indexOf("firstlevel_")==0){
                                        // SecondLevel in FirstLevel
                                        if(more.ref.children.indexOf(node.id) >-1){
                                            // same FirstLevel
                                            return false;
                                        } else {
                                            return true;
                                        }
                                    } else {
                                        return false;
                                    }
                                }
                            }
                        },
                        "dnd": {
                            is_draggable: function (nodes: any) {
                                this.secondLevelSelected = nodes[0].id.replace("secondlevel_", "");
                                if((!isNaN(parseFloat(this.secondLevelSelected)) && isFinite(this.secondLevelSelected)) ||
                                    (!isNaN(parseFloat(nodes[0].id)) && isFinite(nodes[0].id))){
                                    return true;
                                } else {
                                    return false;
                                }
                            }
                        },
                        "plugins": ["search", "dnd", "contextmenu"],
                    });
                    var to: any = false;
                    $j('#jstree_search').keyup(function () {
                        if (to) { clearTimeout(to); }
                        to = setTimeout(function () {
                            var v = $j('#jstree_search').val();
                            $j('#jstree').jstree(true).search(v);
                        }, 250);
                    });
                    $j('#jstree')
                        // listen for event
                        .on('select_node.jstree', (e: any, data: any) => {
                            if (this.sidenavRight.opened == false) {
                                this.sidenavRight.open();
                            }
                            this.loadDoctype(data, false);

                        }).on('move_node.jstree', (e: any, data: any) => {
                            this.loadDoctype(data, true);
                        })
                        // create the instance
                        .jstree();
                }, 0);
                $j('#jstree').jstree('select_node', this.doctypes[0]);
                this.loading = false;
            }, () => {
                location.href = "index.php";
            });
    }

    loadDoctype(data: any, move:boolean) {
        this.creationMode = false;

        // Doctype
        if(data.node.original.type_id){
            this.currentFirstLevel  = false;
            this.currentSecondLevel = false;
            this.http.get("../../rest/doctypes/types/" + data.node.original.type_id )
                .subscribe((dataValue: any) => {
                    this.currentType  = dataValue['doctype'];
                    this.secondLevels = dataValue['secondLevel'];
                    this.processModes = ['NORMAL', 'SVA', 'SVR'];

                    if(move){
                        if(this.currentType){
                            this.newSecondLevel = data.parent.replace("secondlevel_", "");
                            // Is integer
                            if(!isNaN(parseFloat(this.newSecondLevel)) && isFinite(this.newSecondLevel)){
                                if (this.currentType.doctypes_second_level_id != this.newSecondLevel) {
                                    this.currentType.doctypes_second_level_id = this.newSecondLevel;
                                    this.saveType();
                                }
                            } else {
                                alert(this.lang.cantMoveDoctype);
                            }
                        } else {
                            alert(this.lang.noDoctypeSelected);
                        }
                    }

                }, (err) => {
                    this.notify.error(err.error.errors);
                });

        // Second level
        } else if(data.node.original.doctypes_second_level_id) {
            this.currentFirstLevel  = false;
            this.currentType        = false;
            this.http.get("../../rest/doctypes/secondLevel/" + data.node.original.doctypes_second_level_id )
                .subscribe((dataValue: any) => {
                    this.currentSecondLevel = dataValue['secondLevel'];
                    this.firstLevels        = dataValue['firstLevel'];

                    if(move){
                        if(this.currentSecondLevel){
                            this.newFirstLevel = data.parent.replace("firstlevel_", "");
                            // Is integer
                            if(!isNaN(parseFloat(this.newFirstLevel)) && isFinite(this.newFirstLevel)){
                                if (this.currentSecondLevel.doctypes_first_level_id != this.newFirstLevel) {
                                    this.currentSecondLevel.doctypes_first_level_id = this.newFirstLevel;
                                    this.saveSecondLevel();
                                }
                            } else {
                                alert(this.lang.cantMoveFirstLevel);
                            }
                        } else {
                            alert(this.lang.noFirstLevelSelected);
                        }
                    }

                }, (err) => {
                    this.notify.error(err.error.errors);
                });

        // First level
        } else {
            this.currentSecondLevel = false;
            this.currentType        = false;
            this.http.get("../../rest/doctypes/firstLevel/" + data.node.original.doctypes_first_level_id )
                .subscribe((data: any) => {
                    this.currentFirstLevel  = data['firstLevel'];
                    this.folderTypes        = data['folderTypes'];
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
    
    resetDatas() {
        this.currentFirstLevel  = false;
        this.currentSecondLevel = false;
        this.currentType        = false;       
    }

    refreshTree(){
        $j('#jstree').jstree(true).settings.core.data = this.doctypes;
        $j('#jstree').jstree("refresh");
    }

    saveFirstLevel() {
        if (this.creationMode) {
            this.http.post("../../rest/doctypes/firstLevel", this.currentFirstLevel)
                .subscribe((data: any) => {
                    this.resetDatas();
                    this.readMode();
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.firstLevelAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put("../../rest/doctypes/firstLevel/" + this.currentFirstLevel.doctypes_first_level_id, this.currentFirstLevel)
                .subscribe((data: any) => {
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.firstLevelUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    saveSecondLevel() {
        if (this.creationMode) {
            this.http.post("../../rest/doctypes/secondLevel", this.currentSecondLevel)
                .subscribe((data: any) => {
                    this.resetDatas();
                    this.readMode();
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.secondLevelAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put("../../rest/doctypes/secondLevel/" + this.currentSecondLevel.doctypes_second_level_id, this.currentSecondLevel)
                .subscribe((data: any) => {
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.secondLevelUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    saveType() {
        if (this.creationMode) {
            this.http.post("../../rest/doctypes/types", this.currentType)
                .subscribe((data: any) => {
                    this.resetDatas();
                    this.readMode();
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.documentTypeAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put("../../rest/doctypes/types/" + this.currentType.type_id, this.currentType)
                .subscribe((data: any) => {
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    this.notify.success(this.lang.documentTypeUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    readMode() {
        this.creationMode = false;
        $j('#jstree').jstree('deselect_all');
        $j('#jstree').jstree('select_node', this.doctypes[0]);
    }

    removeFirstLevel() {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + this.currentFirstLevel.doctypes_first_level_label + ' »');

        if (r) {
            this.http.delete("../../rest/doctypes/firstLevel/" + this.currentFirstLevel.doctypes_first_level_id)
                .subscribe((data: any) => {
                    this.resetDatas();
                    this.readMode();
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    if(this.doctypes[0]){
                        $j('#jstree').jstree('select_node', this.doctypes[0]);
                    } else if (this.sidenavRight.opened == true) {
                        this.sidenavRight.close();
                    }
                    this.notify.success(this.lang.firstLevelDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    removeSecondLevel() {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + this.currentSecondLevel.doctypes_second_level_label + ' »');

        if (r) {
            this.http.delete("../../rest/doctypes/secondLevel/" + this.currentSecondLevel.doctypes_second_level_id)
                .subscribe((data: any) => {
                    this.resetDatas();
                    this.readMode();
                    this.doctypes = data['doctypeTree'];
                    this.refreshTree();
                    $j('#jstree').jstree('select_node', this.doctypes[0]);
                    this.notify.success(this.lang.secondLevelDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    removeType() {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + this.currentType.description + ' »');

        if (r) {
            this.http.delete("../../rest/doctypes/types/" + this.currentType.type_id)
                .subscribe((data: any) => {
                    if(data.deleted == 0){
                        this.resetDatas();
                        this.readMode();
                        this.doctypes = data['doctypeTree'];
                        this.refreshTree();
                        $j('#jstree').jstree('select_node', this.doctypes[0]);
                        this.notify.success(this.lang.documentTypeDeleted);
                    } else {
                        this.config = { panelClass: 'maarch-modal', data: {count: data.deleted, types: data.doctypes} };
                        this.dialogRef = this.dialog.open(DoctypesAdministrationRedirectModalComponent, this.config);
                        this.dialogRef.afterClosed().subscribe((result: any) => {
                        if (result) {
                            this.http.put("../../rest/doctypes/types/" + this.currentType.type_id + "/redirect", result)
                                .subscribe((data: any) => {
                                    this.resetDatas();
                                    this.readMode();
                                    this.doctypes = data['doctypeTree'];
                                    this.refreshTree();
                                    $j('#jstree').jstree('select_node', this.doctypes[0]);
                                    this.notify.success(this.lang.documentTypeDeleted);
                                }, (err) => {
                                    this.notify.error(err.error.errors);
                                });
                        }
                        this.dialogRef = null;
                        });
                    }

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    prepareDoctypeAdd(mode: any) {
        this.currentFirstLevel  = false;
        this.currentSecondLevel = false;
        this.currentType        = false;
        if(mode == 'firstLevel'){
            this.currentFirstLevel  = {};
        }
        if(mode == 'secondLevel'){
            this.currentSecondLevel  = {};
        }
        if(mode == 'doctype'){
            this.currentType  = {};
        }
        if (this.sidenavRight.opened == false) {
            this.sidenavRight.open();
        }
        $j('#jstree').jstree('deselect_all');
        this.http.get("../../rest/administration/doctypes/new")
            .subscribe((data: any) => {
                this.folderTypes  = data['folderTypes'];
                this.firstLevels  = data['firstLevel'];
                this.secondLevels = data['secondLevel'];
                this.processModes = ['NORMAL', 'SVA', 'SVR'];
            }, (err) => {
                this.notify.error(err.error.errors);
            });
        this.creationMode = mode;
    }
}
@Component({
    templateUrl: "doctypes-administration-redirect-modal.component.html"
})
export class DoctypesAdministrationRedirectModalComponent {
    lang: any = LANG;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<DoctypesAdministrationRedirectModalComponent>) {

    }
}
