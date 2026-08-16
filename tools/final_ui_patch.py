from pathlib import Path

p = Path('resources/views/siberad/dashboards/partials/admin-ui-consistency.blade.php')
text = p.read_text(encoding='utf-8')

role_start = '/* Role & Hak Akses: proporsi kolom dibuat lebih seimbang agar tabel pas di viewport. */'
role_end = '/* Backup Database */'
start = text.find(role_start)
end = text.find(role_end, start)
if start < 0 or end < 0:
    raise SystemExit('Role CSS markers not found')

role_css = '''/* Role & Hak Akses: final layout. */
.main .content [data-tab-panel="role-akses"] .role-akses-table-panel{overflow:hidden!important;width:100%!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table-wrap{overflow-x:hidden!important;width:100%!important;max-width:100%!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table{width:100%!important;min-width:0!important;max-width:100%!important;table-layout:fixed!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th,.main .content [data-tab-panel="role-akses"] .role-akses-table td{min-width:0!important;box-sizing:border-box!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(1),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(1){width:20%!important;padding-left:16px!important;padding-right:12px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(2),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(2){width:25%!important;padding-left:12px!important;padding-right:12px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(3),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(3){width:37%!important;padding-left:12px!important;padding-right:10px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table th:nth-child(4),.main .content [data-tab-panel="role-akses"] .role-akses-table td:nth-child(4){width:18%!important;padding-left:8px!important;padding-right:12px!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-checks{display:flex!important;flex-direction:column!important;align-items:flex-start!important;gap:9px!important;width:100%!important}
.main .content [data-tab-panel="role-akses"] .role-akses-table .role-akses-check{display:flex!important;width:100%!important;max-width:100%!important;white-space:normal!important;min-width:0!important}
.main .content [data-tab-panel="role-akses"] .role-akses-action-head,.main .content [data-tab-panel="role-akses"] .role-akses-action{text-align:center!important;vertical-align:middle!important}
.main .content [data-tab-panel="role-akses"] .role-akses-action button,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn{width:220px!important;max-width:100%!important;min-width:0!important;box-sizing:border-box!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;white-space:nowrap!important;padding:7px 10px!important;font-size:10px!important;letter-spacing:.035em!important}
@media(max-width:760px){.main .content [data-tab-panel="role-akses"] .role-akses-table th,.main .content [data-tab-panel="role-akses"] .role-akses-table td{padding:9px 7px!important}.main .content [data-tab-panel="role-akses"] .role-akses-action button,.main .content [data-tab-panel="role-akses"] .role-akses-action .btn{width:170px!important;font-size:9.5px!important;padding:7px 8px!important}}

'''
text = text[:start] + role_css + text[end:]

backup_css = '''\n<style>\n/* Backup Database: remove the duplicate visual upload form and keep one trigger. */
.main .content [data-tab-panel="backup"] .backup-action-row{display:flex!important;align-items:center!important;gap:0!important;flex-wrap:wrap!important;padding:18px 22px!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form{display:flex!important;align-items:center!important;margin:0!important;padding:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-form{display:none!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form .backup-create-actions{display:flex!important;align-items:center!important;gap:14px!important;margin:0!important;padding:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-actions>.btn{margin:0!important}
.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-trigger{display:inline-flex!important;align-items:center!important;justify-content:center!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0 18px!important;white-space:nowrap!important;box-sizing:border-box!important}
@media(max-width:640px){.main .content [data-tab-panel="backup"] .backup-action-row{padding:14px 16px!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form{width:100%!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-form .backup-create-actions{width:100%!important;gap:10px!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-create-actions>.btn{flex:1 1 0!important}.main .content [data-tab-panel="backup"] .backup-action-row .backup-upload-trigger{height:42px!important;min-height:42px!important}}\n</style>\n'''
if 'remove the duplicate visual upload form and keep one trigger' not in text:
    text += backup_css

p.write_text(text, encoding='utf-8')
print('UI patch applied')
