import { Component, OnInit, Input, Output, EventEmitter, Renderer2 } from '@angular/core';
import { LANG } from '../../translate.component';
import { HttpClient } from '@angular/common/http';
import { map, tap, catchError, filter, exhaustMap, debounceTime, distinctUntilChanged, switchMap, finalize } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '../../notification.service';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { MatDialogRef, MatDialog } from '@angular/material/dialog';
import { FormControl } from '@angular/forms';
import { FoldersService } from '../folders.service';

@Component({
    selector: 'folder-menu',
    templateUrl: "folder-menu.component.html",
    styleUrls: ['folder-menu.component.scss'],
    providers: [NotificationService],
})
export class FolderMenuComponent implements OnInit {

    lang: any = LANG;

    foldersList: any[] = [];
    pinnedFolder: boolean = true;

    loading: boolean = true;

    @Input('resIds') resIds: number[];
    @Input('currentFolders') currentFoldersList: any[];

    @Output('refreshFolders') refreshFolders = new EventEmitter<string>();
    @Output('refreshList') refreshList = new EventEmitter<string>();

    searchTerm: FormControl = new FormControl();

    dialogRef: MatDialogRef<any>;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private renderer: Renderer2,
        private foldersService: FoldersService
    ) { }

    ngOnInit(): void {
        this.searchTerm.valueChanges.pipe(
            debounceTime(300),
            tap((value: any) => {
                if (value.length === 0) {
                    this.pinnedFolder = true;
                    this.getFolders();
                }
            }),
            filter(value => value.length > 2),
            tap(() => this.loading = true),
            //distinctUntilChanged(),
            switchMap(data => this.http.get('../../rest/autocomplete/folders', { params: { "search": data } })),
            tap((data: any) => {
                this.pinnedFolder = false;
                this.foldersList = data.map(
                    (info: any) => {
                        return {
                            id: info.id,
                            label: info.idToDisplay
                        }
                    }
                );
                this.loading = false;
            }),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    initFolderMenu() {
        this.searchTerm.setValue('');
        setTimeout(() => {
            this.renderer.selectRootElement('#searchTerm').focus();
        }, 200);
    }

    getFolders() {
        this.loading = true;
        this.http.get("../../rest/pinnedFolders").pipe(
            map((data: any) => data.folders),
            tap((data: any) => {
                this.foldersList = data;
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    classifyDocuments(folder: any) {

        this.http.post('../../rest/folders/' + folder.id + '/resources', { resources: this.resIds }).pipe(
            tap(() => {
                this.foldersService.getPinnedFolders();
                this.refreshList.emit();
                this.notify.success(this.lang.mailClassified);
            }),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    unclassifyDocuments(folder: any) {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.unclassifyQuestion + ' <b>' + this.resIds.length + '</b>&nbsp;' + this.lang.mailsInFolder + ' ?' } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.request('DELETE', '../../rest/folders/' + folder.id + '/resources', { body: { resources: this.resIds } })),
            tap((data: any) => {
                this.notify.success(this.lang.removedFromFolder);
                this.foldersService.getPinnedFolders();
                this.refreshList.emit();
            })
        ).subscribe();
    }
}
