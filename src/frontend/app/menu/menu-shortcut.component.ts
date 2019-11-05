import { Component, OnInit, Inject }    from '@angular/core';
import { Router }               from '@angular/router';
import { HttpClient }           from '@angular/common/http';
import { LANG }                 from '../translate.component';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialog } from '@angular/material/dialog';
import { HeaderService } from '../../service/header.service';
import { AppService } from '../../service/app.service';
import { PrivilegeService } from '../../service/privileges.service';

declare function $j(selector: any) : any;

@Component({
    selector: 'menu-shortcut',
    styleUrls: ['menu-shortcut.component.scss'],
    templateUrl : "menu-shortcut.component.html",
})
export class MenuShortcutComponent implements OnInit {

    lang        : any       = LANG;
    router      : any;
    dialogRef   : MatDialogRef<any>;
    config      : any       = {};
    speedDialFabButtons : any = [];
    speedDialFabColumnDirection = 'column';
    shortcuts: any;


    constructor(
        public http: HttpClient, 
        private _router: Router, 
        public dialog: MatDialog,
        public appService: AppService,
        public privilegeService: PrivilegeService
    ) {
        this.router = _router;
        /**/
    }

    ngOnInit(): void {
        this.shortcuts = this.privilegeService.getCurrentUserShortcuts();
    }

    onSpeedDialFabClicked(group: any, shortcut:any) {
        this.router.navigate(['/indexing/' + group.id]);
    }

    gotToMenu(shortcut:any) {
        if (shortcut.id === 'indexing') {
            this.router.navigate([shortcut.route + '/' + shortcut.groups[0].id]);
        } else if (shortcut.angular === true) {
            this.router.navigate([shortcut.route]);
        } else {
            location.href = shortcut.route;
        }
    }
}
@Component({
    templateUrl: "indexing-group-modal.component.html",
    styles: [".mat-dialog-content{max-height: 65vh;width:600px;}"]
})
export class IndexingGroupModalComponent {
    lang: any = LANG;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<IndexingGroupModalComponent>) {
    }
}