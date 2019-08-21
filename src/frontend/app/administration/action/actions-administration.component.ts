import { Component, ViewChild, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { HeaderService }        from '../../../service/header.service';
import { AppService } from '../../../service/app.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "actions-administration.component.html",
    providers: [NotificationService, AppService]
})

export class ActionsAdministrationComponent implements OnInit {

    @ViewChild('snav', { static: false }) public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2', { static: false }) public sidenavRight  : MatSidenav;
    
    lang: any = LANG;
    search: string = null;

    actions: any[] = [];
    titles: any[] = [];

    loading: boolean = false;

    displayedColumns = ['id', 'label_action', 'history', 'actions'];
    dataSource = new MatTableDataSource(this.actions);
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    }

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService
        ) {
            $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(null);

        this.loading = true;

        this.http.get('../../rest/actions')
            .subscribe((data) => {
                this.actions = data['actions'];
                this.headerService.setHeader(this.lang.administration + ' ' + this.lang.actions);
                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.actions);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
            }, (err) => {
                console.log(err);
                location.href = "index.php";
            });
    }

    deleteAction(action: any) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + action.label_action + ' »');

        if (r) {
            this.http.delete('../../rest/actions/' + action.id)
                .subscribe((data: any) => {
                    this.actions = data.actions;
                    this.dataSource = new MatTableDataSource(this.actions);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.notify.success(this.lang.actionDeleted);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
