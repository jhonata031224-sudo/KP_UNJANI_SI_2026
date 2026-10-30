<style>
/* Selaraskan area isi dashboard dengan sisi kanan topbar/profile pada semua kondisi sidebar. */
.main > .content,
.main .content {
  width: 100% !important;
  max-width: none !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  box-sizing: border-box !important;
  padding-left: 32px !important;
  padding-right: 32px !important;
}
.main .content > .pimp-page,
.main .content > .report-page,
.main .content > .page,
.main .content > .container {
  width: 100% !important;
  max-width: none !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  box-sizing: border-box !important;
}
/* Halaman pimpinan sebelumnya memiliki batas 1500px; lepaskan batas itu agar
   sisi kanan selalu mengikuti padding topbar, baik sidebar terbuka maupun ciut. */
.main .content .pimp-page { max-width: none !important; width: 100% !important; }
@media (max-width: 900px) {
  .main > .content,
  .main .content { padding-left: 20px !important; padding-right: 20px !important; }
}
@media (max-width: 600px) {
  .main > .content,
  .main .content { padding-left: 14px !important; padding-right: 14px !important; }
}
</style>
