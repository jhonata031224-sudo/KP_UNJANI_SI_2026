<style>
  /* KHUSUS SIDEBAR DANPUS: submenu rapat dan seimbang. */
  .side-nav-group .side-dropdown-menu {
    padding: 0 !important;
    margin: 0 !important;
  }

  .side-nav-group .side-sub-link {
    display: flex !important;
    align-items: center !important;
    gap: 0 !important;
    margin: 0 !important;
    padding: 2px 0 2px 20px !important;
    min-height: 32px !important;
    height: 32px !important;
    box-sizing: border-box !important;
    text-transform: none !important;
  }

  .side-nav-group .side-dropdown-menu .side-sub-link:first-child {
    margin-top: 2px !important;
  }

  .side-nav-group .side-dropdown-menu .side-sub-link + .side-sub-link {
    margin-top: 0 !important;
  }

  .side-nav-group .side-sub-link .sub-dot,
  .side-nav-group .side-dropdown-menu .side-sub-link::before,
  .side-nav-group .side-dropdown-menu .side-sub-link::after {
    display: none !important;
    content: none !important;
  }

  /* Log aktivitas Satlak: menggantikan tampilan tabel menjadi timeline. */
  .danpus-activity-log {
    display: flex;
    flex-direction: column;
    gap: 0;
    margin-top: 4px;
  }

  .danpus-activity-item {
    position: relative;
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr);
    column-gap: 13px;
    padding: 0 0 18px;
  }

  .danpus-activity-item:last-child {
    padding-bottom: 0;
  }

  .danpus-activity-line {
    position: absolute;
    left: 6px;
    top: 13px;
    bottom: 0;
    width: 1px;
    background: var(--p-border);
  }

  .danpus-activity-item:last-child .danpus-activity-line {
    display: none;
  }

  .danpus-activity-dot {
    position: relative;
    z-index: 2;
    width: 13px;
    height: 13px;
    margin-top: 4px;
    border-radius: 50%;
    background: var(--p-accent);
    border: 3px solid var(--p-surface);
    box-shadow: 0 0 0 1px var(--p-border);
  }

  .danpus-activity-card {
    background: var(--p-surface-2);
    border: 1px solid var(--p-border);
    border-radius: 12px;
    padding: 13px 15px;
    min-width: 0;
  }

  .danpus-activity-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
  }

  .danpus-activity-subject {
    font-size: 13px;
    font-weight: 800;
    color: var(--p-text);
    line-height: 1.4;
  }

  .danpus-activity-project {
    margin-top: 3px;
    font-size: 10px;
    color: var(--p-muted);
  }

  .danpus-activity-date {
    flex: 0 0 auto;
    font-size: 10px;
    color: var(--p-muted);
    white-space: nowrap;
  }

  .danpus-activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 10px;
  }

  .danpus-activity-meta span {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border: 1px solid var(--p-border);
    border-radius: 999px;
    background: var(--p-surface);
    color: var(--p-muted);
    font-size: 9px;
    font-weight: 700;
  }

  .danpus-activity-action {
    margin-top: 10px;
  }

  .danpus-activity-action .detail-btn {
    padding: 6px 10px;
  }

  .danpus-activity-empty {
    padding: 25px 12px;
    text-align: center;
    color: var(--p-muted);
    font-size: 12px;
    border: 1px dashed var(--p-border);
    border-radius: 12px;
  }

  @media (max-width: 700px) {
    .danpus-activity-head {
      display: block;
    }

    .danpus-activity-date {
      margin-top: 5px;
    }
  }
</style>
<script>
  (function () {
    function normalizeDanpusSubmenuText() {
      var labels = {
        'SATLAKKAL': 'Satlakkal',
        'SATLAKSISOS': 'Satlaksisos',
        'SATLAKDAK': 'Satlakdak',
        'SATLAKDUKTEK': 'Satlakduktek'
      };

      document.querySelectorAll('.side-nav-group .side-sub-link').forEach(function (link) {
        var text = link.textContent.trim();
        if (labels[text]) {
          Array.prototype.slice.call(link.childNodes).forEach(function (node) {
            if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim()) {
              node.nodeValue = node.nodeValue.replace(text, labels[text]);
            }
          });
        }
      });
    }

    function removeDanpusSubmenuDots() {
      document.querySelectorAll('.side-nav-group .side-sub-link .sub-dot').forEach(function (dot) {
        dot.remove();
      });
    }

    function removeDanpusProfileHelpText() {
      var profileHelp = document.querySelector('#profilePhotoView .profile-help-text');
      if (profileHelp) {
        profileHelp.remove();
      }
    }

    function buildActivityTimeline() {
      /* Perubahan ini khusus Danpus. Wadan tetap memakai tampilan yang sudah ada. */
      @if(($satuan->kode ?? null) !== 'DANPUS')
        return;
      @endif

      document.querySelectorAll('section[id^="satlak-"] .clean-table-wrap').forEach(function (wrapper) {
        if (wrapper.dataset.timelineReady === '1') return;
        var table = wrapper.querySelector('table');
        if (!table) return;

        var rows = Array.from(table.querySelectorAll('tbody tr'));
        var validRows = rows.filter(function (row) {
          return row.querySelector('td') && row.querySelector('.detail-btn');
        });

        var timeline = document.createElement('div');
        timeline.className = 'danpus-activity-log';

        if (!validRows.length) {
          var empty = document.createElement('div');
          empty.className = 'danpus-activity-empty';
          empty.textContent = 'Belum ada aktivitas dari satuan ini.';
          timeline.appendChild(empty);
        } else {
          validRows.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            var detail = row.querySelector('.detail-btn');
            var item = document.createElement('article');
            item.className = 'danpus-activity-item';

            var dot = document.createElement('div');
            dot.className = 'danpus-activity-dot';
            item.appendChild(dot);

            if (validRows.length > 1) {
              var line = document.createElement('div');
              line.className = 'danpus-activity-line';
              item.appendChild(line);
            }

            var card = document.createElement('div');
            card.className = 'danpus-activity-card';

            var head = document.createElement('div');
            head.className = 'danpus-activity-head';

            var titleWrap = document.createElement('div');
            var subject = document.createElement('div');
            subject.className = 'danpus-activity-subject';
            subject.textContent = cells[0]?.querySelector('.subject')?.textContent.trim() || cells[0]?.textContent.trim() || 'Aktivitas laporan';
            titleWrap.appendChild(subject);

            var project = cells[0]?.querySelector('.muted');
            if (project) {
              var projectEl = document.createElement('div');
              projectEl.className = 'danpus-activity-project';
              projectEl.textContent = project.textContent.trim();
              titleWrap.appendChild(projectEl);
            }

            var date = document.createElement('div');
            date.className = 'danpus-activity-date';
            date.textContent = cells[4]?.textContent.trim() || '-';

            head.appendChild(titleWrap);
            head.appendChild(date);
            card.appendChild(head);

            var meta = document.createElement('div');
            meta.className = 'danpus-activity-meta';

            [
              ['Tujuan', cells[1]?.textContent.trim() || '-'],
              ['Prioritas', cells[2]?.textContent.trim() || '-'],
              ['Status', cells[3]?.textContent.trim() || '-']
            ].forEach(function (entry) {
              var badge = document.createElement('span');
              badge.textContent = entry[0] + ': ' + entry[1];
              meta.appendChild(badge);
            });
            card.appendChild(meta);

            if (detail) {
              var action = document.createElement('div');
              action.className = 'danpus-activity-action';
              action.appendChild(detail.cloneNode(true));
              card.appendChild(action);
            }

            item.appendChild(card);
            timeline.appendChild(item);
          });
        }

        wrapper.innerHTML = '';
        wrapper.appendChild(timeline);
        wrapper.dataset.timelineReady = '1';
      });
    }

    function applyDanpusSubmenuFix() {
      removeDanpusSubmenuDots();
      normalizeDanpusSubmenuText();
      removeDanpusProfileHelpText();
      buildActivityTimeline();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyDanpusSubmenuFix);
    } else {
      applyDanpusSubmenuFix();
    }
  })();
</script>
