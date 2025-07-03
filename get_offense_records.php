<script>
  const csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";
  const showArchived = <?php echo $show_archived ? '1' : '0'; ?>;

  document.addEventListener('DOMContentLoaded', () => {
    const loadingDiv = document.getElementById('loading');
    const citationTable = document.getElementById('citationTable');
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const content = document.querySelector('.content');
    const openModals = new Set();

    // Helper functions to show and hide modals
    const showModal = (modal) => {
      if (openModals.size > 0) return; // Prevent multiple modals
      openModals.add(modal);
      modal.style.display = 'flex';
      setTimeout(() => modal.classList.add('show'), 10);
    };

    const hideModal = (modal) => {
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
        openModals.delete(modal);
      }, 300);
    };

    // Sidebar toggle
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      content.style.marginLeft = sidebar.classList.contains('open') ? '200px' : '0';
    });

    // Lazy load table
    loadingDiv.style.display = 'block';
    citationTable.style.opacity = '0';
    setTimeout(() => {
      loadingDiv.style.display = 'none';
      citationTable.style.opacity = '1';
    }, 300);

    // Table row hover effects
    const updateRowHoverEffects = () => {
      const rows = document.querySelectorAll('.table tr');
      rows.forEach(row => {
        row.addEventListener('mouseenter', () => {
          row.style.cursor = 'pointer';
        });
        row.addEventListener('mouseleave', () => {
          row.style.cursor = 'default';
        });
      });
    };
    updateRowHoverEffects();

    // Fetch table data
    const sortSelect = document.getElementById('sortSelect');
    const searchInput = document.getElementById('searchInput');
    const recordsPerPageSelect = document.getElementById('recordsPerPage');
    const urlParams = new URLSearchParams(window.location.search);
    const sortParam = urlParams.get('sort') || 'apprehension_desc';
    const searchParam = urlParams.get('search') || '';
    const recordsPerPage = urlParams.get('records_per_page') || '20';
    sortSelect.value = sortParam;
    searchInput.value = searchParam;
    recordsPerPageSelect.value = recordsPerPage;
    let currentPage = parseInt(urlParams.get('page')) || 1;

    function fetchTableData(search, sort, showArchived, page, recordsPerPage) {
      loadingDiv.style.display = 'block';
      citationTable.style.opacity = '0';
      const params = new URLSearchParams({
        search: encodeURIComponent(search),
        sort: encodeURIComponent(sort),
        show_archived: encodeURIComponent(showArchived),
        page: encodeURIComponent(page),
        records_per_page: encodeURIComponent(recordsPerPage),
        csrf_token: encodeURIComponent(csrfToken)
      });
      fetch('fetch_citations.php?' + params.toString(), {
        method: 'GET',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })
      .then(response => {
        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
        return response.text();
      })
      .then(data => {
        if (data.trim() === '') {
          citationTable.innerHTML = "<p class='empty-state'><i class='fas fa-info-circle'></i> No citations found.</p>";
        } else {
          citationTable.innerHTML = data;
        }
        loadingDiv.style.display = 'none';
        citationTable.style.opacity = '1';
        updateRowHoverEffects();
        attachEventListeners();
        updatePagination(page, parseInt(recordsPerPage));
      })
      .catch(error => {
        loadingDiv.style.display = 'none';
        citationTable.innerHTML = `<p class='debug'><i class='fas fa-exclamation-circle'></i> Error: ${error.message}</p>`;
        console.error('Fetch table error:', error);
      });
    }

    // Initial data fetch
    fetchTableData(searchParam, sortParam, showArchived, currentPage, recordsPerPage);

    // Sort functionality
    sortSelect.addEventListener('change', () => {
      currentPage = 1;
      fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
    });

    // Search functionality
    searchInput.addEventListener('input', debounce(() => {
      currentPage = 1;
      fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
    }, 500));

    // Records per page functionality
    recordsPerPageSelect.addEventListener('change', () => {
      currentPage = 1;
      fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
    });

    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Enhanced Pagination
    function updatePagination(currentPage, recordsPerPage) {
      const paginationContainer = document.getElementById('paginationContainer');
      const pagination = document.getElementById('pagination');
      const totalRecords = parseInt(paginationContainer.dataset.totalRecords);
      const totalPages = parseInt(paginationContainer.dataset.totalPages);
      const maxPagesToShow = 5;

      pagination.innerHTML = '';

      // Previous button
      const prevLi = document.createElement('li');
      prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
      prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">«</a>`;
      pagination.appendChild(prevLi);

      // Page numbers with ellipsis
      let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
      let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

      if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
      }

      if (startPage > 1) {
        const firstPage = document.createElement('li');
        firstPage.className = 'page-item';
        firstPage.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
        pagination.appendChild(firstPage);

        if (startPage > 2) {
          const ellipsis = document.createElement('li');
          ellipsis.className = 'page-item disabled';
          ellipsis.innerHTML = `<span class="ellipsis">...</span>`;
          pagination.appendChild(ellipsis);
        }
      }

      for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        pagination.appendChild(pageLi);
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          const ellipsis = document.createElement('li');
          ellipsis.className = 'page-item disabled';
          ellipsis.innerHTML = `<span class="ellipsis">...</span>`;
          pagination.appendChild(ellipsis);
        }

        const lastPage = document.createElement('li');
        lastPage.className = 'page-item';
        lastPage.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
        pagination.appendChild(lastPage);
      }

      // Next button
      const nextLi = document.createElement('li');
      nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
      nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">»</a>`;
      pagination.appendChild(nextLi);

      // Update pagination info
      const recordStart = (currentPage - 1) * recordsPerPage + 1;
      const recordEnd = Math.min(currentPage * recordsPerPage, totalRecords);
      document.getElementById('recordStart').textContent = recordStart;
      document.getElementById('recordEnd').textContent = recordEnd;
      document.getElementById('totalRecords').textContent = totalRecords;

      // Attach event listeners to page links
      document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const page = parseInt(link.getAttribute('data-page'));
          if (page && !link.parentElement.classList.contains('disabled')) {
            currentPage = page;
            fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
          }
        });
      });
    }

    // Archive modal handling
    const archiveModal = document.getElementById('archiveModal');
    const closeArchiveModal = document.getElementById('cancelArchive');
    const confirmArchive = document.getElementById('confirmArchive');
    const remarksReason = document.getElementById('remarksReason');
    const errorMessage = document.getElementById('errorMessage');
    let currentCitationId = null;
    let currentAction = null;
    let isTRO = null;

    const attachEventListeners = () => {
      document.querySelectorAll('.archive-btn').forEach(button => {
        button.addEventListener('click', () => {
          currentCitationId = button.getAttribute('data-id');
          currentAction = button.getAttribute('data-action');
          isTRO = button.getAttribute('data-is-tro') === '1';
          showModal(archiveModal);
          remarksReason.value = '';
          errorMessage.style.display = 'none';
          remarksReason.focus();
          if (isTRO) {
            remarksReason.setAttribute('required', 'required');
            document.querySelector('#archiveModal h2').textContent = 'Remarks Note: Reason for TRO Archiving';
          } else {
            remarksReason.removeAttribute('required');
            document.querySelector('#archiveModal h2').textContent = 'Remarks Note: Reason for Archiving';
          }
        });
      });

      document.querySelectorAll('.driver-link').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const driverId = link.getAttribute('data-driver-id');
          const zone = link.getAttribute('data-zone');
          const barangay = link.getAttribute('data-barangay');
          const municipality = link.getAttribute('data-municipality');
          const province = link.getAttribute('data-province');

          loadingDiv.style.display = 'block';
          console.log('Fetching driver info for driverId:', driverId);
          fetch(`get_driver_info.php?driver_id=${encodeURIComponent(driverId)}`, {
            headers: { 'Accept': 'application/json' }
          })
            .then(response => {
              if (!response.ok) {
                return response.text().then(text => {
                  throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                });
              }
              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                  throw new Error(`Unexpected content type: ${contentType}, Response: ${text}`);
                });
              }
              return response.json();
            })
            .then(data => {
              if (data.status === 'error') {
                throw new Error(data.error);
              }

              loadingDiv.style.display = 'none';
              document.getElementById('licenseNumber').textContent = data.license_number || 'N/A';
              document.getElementById('driverName').textContent = data.driver_name || 'N/A';
              document.getElementById('driverAddress').textContent = `${zone ? zone + ', ' : ''}${barangay ? barangay + ', ' : ''}${municipality}, ${province}`;
              const offenseTable = document.getElementById('offenseRecords');
              offenseTable.innerHTML = '';
              let totalFine = 0;
              data.offenses.forEach(offense => {
                const fine = parseFloat(offense.fine) || 150.00;
                totalFine += fine;
                const row = document.createElement('tr');
                row.innerHTML = `
                  <td>${offense.date_time || 'N/A'}</td>
                  <td>${offense.offense}${offense.offense_count ? ' (Offense ' + offense.offense_count + ')' : ''}</td>
                  <td>₱${fine.toFixed(2)}</td>
                  <td>${offense.status || 'Unpaid'}</td>
                `;
                offenseTable.appendChild(row);
              });
              document.getElementById('totalFines').textContent = `₱${totalFine.toFixed(2)}`;
              document.getElementById('totalFineDisplay').textContent = `₱${totalFine.toFixed(2)}`;

              const modal = document.getElementById('driverInfoModal');
              showModal(modal);
            })
            .catch(error => {
              loadingDiv.style.display = 'none';
              document.getElementById('licenseNumber').textContent = 'Error';
              document.getElementById('offenseRecords').innerHTML = `<tr><td colspan="4">Error loading driver data: ${error.message}</td></tr>`;
              console.error('Driver info fetch error:', error.message);
              alert(`Failed to load driver information: ${error.message}`);
            });
        });
      });

      document.querySelectorAll('.pay-now').forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const citationId = button.getAttribute('data-citation-id');
          const driverId = button.getAttribute('data-driver-id');
          const zone = button.getAttribute('data-zone');
          const barangay = button.getAttribute('data-barangay');
          const municipality = button.getAttribute('data-municipality');
          const province = button.getAttribute('data-province');

          loadingDiv.style.display = 'block';
          console.log('Pay Now clicked for citationId:', citationId, 'driverId:', driverId);
          fetch(`get_offense_records.php?citation_id=${encodeURIComponent(citationId)}`, {
            headers: { 'Accept': 'application/json' }
          })
            .then(response => {
              if (!response.ok) {
                return response.text().then(text => {
                  throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                });
              }
              const contentType = response.headers.get('content-type');
              if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                  throw new Error(`Unexpected content type: ${contentType}, Response: ${text}`);
                });
              }
              return response.json();
            })
            .then(data => {
              if (data.error) {
                throw new Error(data.error);
              }

              loadingDiv.style.display = 'none';
              document.getElementById('paymentLicenseNumber').textContent = button.closest('tr').querySelector('td:nth-child(4)').textContent || 'N/A';
              document.getElementById('paymentDriverName').textContent = button.closest('tr').querySelector('.driver-link').textContent || 'N/A';
              document.getElementById('paymentDriverAddress').textContent = `${zone ? zone + ', ' : ''}${barangay ? barangay + ', ' : ''}${municipality}, ${province}`;
              
              const offenseTable = document.getElementById('paymentOffenseRecords');
              offenseTable.innerHTML = '';
              let totalFine = 0;
              let unpaidFine = 0;

              console.log('Violation data for citation', citationId, ':', data); // Debug log
              data.forEach(record => {
                const fine = parseFloat(record.fine) || 150.00;
                totalFine += fine;
                const isPaid = record.violation_payment_status === 'Paid';
                if (!isPaid) {
                  unpaidFine += fine;
                }
                const row = document.createElement('tr');
                row.innerHTML = `
                  <td><input type="checkbox" class="violation-checkbox" data-violation-id="${record.violation_id}" data-fine="${fine}" ${isPaid ? 'disabled' : 'checked'}></td>
                  <td>${record.apprehension_datetime || 'N/A'}</td>
                  <td>${record.violation_type || 'Unknown'}</td>
                  <td>₱${fine.toFixed(2)}</td>
                  <td>${isPaid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>'}</td>
                `;
                offenseTable.appendChild(row);
              });

              document.getElementById('paymentTotalFines').textContent = `₱${totalFine.toFixed(2)}`;
              document.getElementById('paymentTotalFineDisplay').textContent = `₱${totalFine.toFixed(2)}`;
              document.getElementById('amountDue').textContent = `₱${unpaidFine.toFixed(2)}`;

              const cashInput = document.getElementById('cashInput');
              const changeDisplay = document.getElementById('changeDisplay');
              const paymentError = document.getElementById('paymentError');

              cashInput.value = unpaidFine.toFixed(2);
              changeDisplay.textContent = '₱0.00';
              paymentError.style.display = 'none';

              const newCashInput = cashInput.cloneNode(true);
              cashInput.parentNode.replaceChild(newCashInput, cashInput);

              newCashInput.addEventListener('input', () => {
                const cash = parseFloat(newCashInput.value) || 0;
                let due = 0;
                document.querySelectorAll('.violation-checkbox:checked').forEach(cb => {
                  due += parseFloat(cb.dataset.fine) || 0;
                });
                const change = cash - due;
                document.getElementById('amountDue').textContent = `₱${due.toFixed(2)}`;
                changeDisplay.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
                if (change < 0) {
                  paymentError.textContent = 'Insufficient cash amount.';
                  paymentError.style.display = 'block';
                } else {
                  paymentError.style.display = 'none';
                }
              });

              document.querySelectorAll('.violation-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                  let due = 0;
                  document.querySelectorAll('.violation-checkbox:checked').forEach(cb => {
                    due += parseFloat(cb.dataset.fine) || 0;
                  });
                  document.getElementById('amountDue').textContent = `₱${due.toFixed(2)}`;
                  const cash = parseFloat(newCashInput.value) || 0;
                  const change = cash - due;
                  changeDisplay.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
                  if (change < 0) {
                    paymentError.textContent = 'Insufficient cash amount.';
                    paymentError.style.display = 'block';
                  } else {
                    paymentError.style.display = 'none';
                  }
                });
              });

              const paymentModal = document.getElementById('paymentModal');
              paymentModal.dataset.citationId = citationId;
              showModal(paymentModal);
            })
            .catch(error => {
              loadingDiv.style.display = 'none';
              console.error('Pay Now fetch error for citationId', citationId, ':', error.message);
              alert(`Failed to load payment data: ${error.message}`);
              // Retry once if it's a database error
              if (error.message.includes('Database error')) {
                console.log('Retrying fetch for citationId:', citationId);
                fetch(`get_offense_records.php?citation_id=${encodeURIComponent(citationId)}`, {
                  headers: { 'Accept': 'application/json' }
                })
                .then(response => {
                  if (!response.ok) throw new Error(`Retry failed: HTTP error! Status: ${response.status}`);
                  const contentType = response.headers.get('content-type');
                  if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                      throw new Error(`Retry failed: Unexpected content type: ${contentType}, Response: ${text}`);
                    });
                  }
                  return response.json();
                })
                .then(data => {
                  if (data.error) throw new Error(data.error);
                  // Process data as in the first fetch
                  loadingDiv.style.display = 'none';
                  document.getElementById('paymentLicenseNumber').textContent = button.closest('tr').querySelector('td:nth-child(4)').textContent || 'N/A';
                  document.getElementById('paymentDriverName').textContent = button.closest('tr').querySelector('.driver-link').textContent || 'N/A';
                  document.getElementById('paymentDriverAddress').textContent = `${zone ? zone + ', ' : ''}${barangay ? barangay + ', ' : ''}${municipality}, ${province}`;
                  
                  const offenseTable = document.getElementById('paymentOffenseRecords');
                  offenseTable.innerHTML = '';
                  let totalFine = 0;
                  let unpaidFine = 0;

                  console.log('Retry violation data for citation', citationId, ':', data);
                  data.forEach(record => {
                    const fine = parseFloat(record.fine) || 150.00;
                    totalFine += fine;
                    const isPaid = record.violation_payment_status === 'Paid';
                    if (!isPaid) {
                      unpaidFine += fine;
                    }
                    const row = document.createElement('tr');
                    row.innerHTML = `
                      <td><input type="checkbox" class="violation-checkbox" data-violation-id="${record.violation_id}" data-fine="${fine}" ${isPaid ? 'disabled' : 'checked'}></td>
                      <td>${record.apprehension_datetime || 'N/A'}</td>
                      <td>${record.violation_type || 'Unknown'}</td>
                      <td>₱${fine.toFixed(2)}</td>
                      <td>${isPaid ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-danger">Unpaid</span>'}</td>
                    `;
                    offenseTable.appendChild(row);
                  });

                  document.getElementById('paymentTotalFines').textContent = `₱${totalFine.toFixed(2)}`;
                  document.getElementById('paymentTotalFineDisplay').textContent = `₱${totalFine.toFixed(2)}`;
                  document.getElementById('amountDue').textContent = `₱${unpaidFine.toFixed(2)}`;

                  const cashInput = document.getElementById('cashInput');
                  const changeDisplay = document.getElementById('changeDisplay');
                  const paymentError = document.getElementById('paymentError');

                  cashInput.value = unpaidFine.toFixed(2);
                  changeDisplay.textContent = '₱0.00';
                  paymentError.style.display = 'none';

                  const newCashInput = cashInput.cloneNode(true);
                  cashInput.parentNode.replaceChild(newCashInput, cashInput);

                  newCashInput.addEventListener('input', () => {
                    const cash = parseFloat(newCashInput.value) || 0;
                    let due = 0;
                    document.querySelectorAll('.violation-checkbox:checked').forEach(cb => {
                      due += parseFloat(cb.dataset.fine) || 0;
                    });
                    const change = cash - due;
                    document.getElementById('amountDue').textContent = `₱${due.toFixed(2)}`;
                    changeDisplay.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
                    if (change < 0) {
                      paymentError.textContent = 'Insufficient cash amount.';
                      paymentError.style.display = 'block';
                    } else {
                      paymentError.style.display = 'none';
                    }
                  });

                  document.querySelectorAll('.violation-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                      let due = 0;
                      document.querySelectorAll('.violation-checkbox:checked').forEach(cb => {
                        due += parseFloat(cb.dataset.fine) || 0;
                      });
                      document.getElementById('amountDue').textContent = `₱${due.toFixed(2)}`;
                      const cash = parseFloat(newCashInput.value) || 0;
                      const change = cash - due;
                      changeDisplay.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
                      if (change < 0) {
                        paymentError.textContent = 'Insufficient cash amount.';
                        paymentError.style.display = 'block';
                      } else {
                        paymentError.style.display = 'none';
                      }
                    });
                  });

                  const paymentModal = document.getElementById('paymentModal');
                  paymentModal.dataset.citationId = citationId;
                  showModal(paymentModal);
                })
                .catch(error => {
                  loadingDiv.style.display = 'none';
                  console.error('Retry fetch error for citationId', citationId, ':', error.message);
                  alert(`Failed to load payment data after retry: ${error.message}`);
                });
              }
            });
        });
      });

      closeArchiveModal.addEventListener('click', () => {
        hideModal(archiveModal);
        errorMessage.style.display = 'none';
      });

      confirmArchive.addEventListener('click', () => {
        const reason = remarksReason.value.trim();
        errorMessage.style.display = 'none';

        if (isTRO && !reason) {
          errorMessage.textContent = 'A remarks note is required for archiving/unarchiving a TRO.';
          errorMessage.style.display = 'block';
          return;
        }

        if (reason.length > 255) {
          errorMessage.textContent = 'Remarks note exceeds 255 characters.';
          errorMessage.style.display = 'block';
          return;
        }

        fetch('archive_citation.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${encodeURIComponent(currentCitationId)}&archive=${encodeURIComponent(currentAction)}&remarksReason=${encodeURIComponent(reason)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          alert(data.message);
          if (data.status === 'success') {
            fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
          }
        })
        .catch(error => {
          alert('Error archiving citation: ' + error.message);
          console.error('Archive citation error:', error);
        });

        hideModal(archiveModal);
      });

      let isOutsideClick = false;
      window.addEventListener('click', (event) => {
        if (event.target === archiveModal && !isOutsideClick) {
          isOutsideClick = true;
          hideModal(archiveModal);
          errorMessage.style.display = 'none';
        } else {
          isOutsideClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && archiveModal.classList.contains('show')) {
          hideModal(archiveModal);
          errorMessage.style.display = 'none';
        }
      });

      // Bulk actions
      document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.select-citation').forEach(checkbox => {
          checkbox.checked = e.target.checked;
        });
      });

      document.getElementById('applyBulk').addEventListener('click', () => {
        const action = document.getElementById('bulkActions').value;
        if (!action) return alert('Please select an action.');

        const selected = Array.from(document.querySelectorAll('.select-citation:checked')).map(checkbox => checkbox.value);
        if (selected.length === 0) return alert('Please select at least one citation.');

        if (action === 'delete' && !confirm('Are you sure you want to delete the selected citations?')) return;

        fetch('bulk_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `action=${encodeURIComponent(action)}&ids=${encodeURIComponent(JSON.stringify(selected))}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          return response.json();
        })
        .then(data => {
          alert(data.message);
          if (data.status === 'success') fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
        })
        .catch(error => alert('Error: ' + error.message));
      });

      // Export to CSV
      document.getElementById('exportCSV').addEventListener('click', () => {
        const rows = document.querySelectorAll('#citationTable table tr');
        let csv = [];
        const headers = Array.from(rows[0].querySelectorAll('th')).map(th => th.textContent.trim().replace(/[\n\r]+|[\s]{2,}/g, ' '));
        csv.push(headers.join(','));

        for (let i = 1; i < rows.length; i++) {
          const cols = Array.from(rows[i].querySelectorAll('td')).map(td => {
            let text = td.textContent.trim().replace(/"/g, '""').replace(/[\n\r]+|[\s]{2,}/g, ' ');
            if (text.match(/^[+=@-]/)) text = `'${text}`;
            return `"${text}"`;
          });
          csv.push(cols.join(','));
        }

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Traffic_Citation_Records.csv';
        link.click();
      });

      // Toggle timeline view
      document.getElementById('toggleView').addEventListener('click', () => {
        const tableView = document.querySelector('#citationTable table');
        const timelineView = document.getElementById('timelineView');
        if (tableView && tableView.style.display !== 'none') {
          tableView.style.display = 'none';
          timelineView.style.display = 'block';
          document.getElementById('toggleView').innerHTML = '<i class="fas fa-table"></i> Table View';

          const rows = document.querySelectorAll('#citationTable table tbody tr');
          const timelineContainer = timelineView.querySelector('.timeline-container');
          timelineContainer.innerHTML = '';
          rows.forEach(row => {
            const cols = row.querySelectorAll('td');
            const item = document.createElement('div');
            item.className = 'timeline-item';
            item.innerHTML = `
              <h5>${cols[1].textContent} - ${cols[2].textContent}</h5>
              <p><strong>Date:</strong> ${cols[6].textContent}</p>
              <p><strong>Violations:</strong> ${cols[7].textContent}</p>
              <p><strong>Vehicle:</strong> ${cols[4].textContent} (${cols[5].textContent})</p>
              <p><strong>Payment Status:</strong> ${cols[8].textContent}</p>
              <p><strong>Reference Number:</strong> ${cols[9].textContent}</p>
            `;
            timelineContainer.appendChild(item);
          });
        } else {
          tableView.style.display = 'block';
          timelineView.style.display = 'none';
          document.getElementById('toggleView').innerHTML = '<i class="fas fa-stream"></i> Timeline View';
        }
      });

      document.getElementById('closeModal').addEventListener('click', () => {
        const modal = document.getElementById('driverInfoModal');
        hideModal(modal);
      });

      document.getElementById('printModal').addEventListener('click', () => {
        window.print();
      });

      document.querySelector('#driverInfoModal .close').addEventListener('click', () => {
        const modal = document.getElementById('driverInfoModal');
        hideModal(modal);
      });

      let isDriverModalClick = false;
      window.addEventListener('click', (event) => {
        const modal = document.getElementById('driverInfoModal');
        if (event.target === modal && !isDriverModalClick) {
          isDriverModalClick = true;
          hideModal(modal);
        } else {
          isDriverModalClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.getElementById('driverInfoModal').classList.contains('show')) {
          const modal = document.getElementById('driverInfoModal');
          hideModal(modal);
        }
      });

      document.getElementById('confirmPayment').addEventListener('click', () => {
        const cashInput = document.getElementById('cashInput');
        const changeDisplay = document.getElementById('changeDisplay');
        const paymentError = document.getElementById('paymentError');
        const paymentModal = document.getElementById('paymentModal');
        const citationId = paymentModal.dataset.citationId;
        const cash = parseFloat(cashInput.value) || 0;
        const unpaidFines = parseFloat(document.getElementById('amountDue').textContent.replace('₱', '')) || 0;

        if (cash < unpaidFines) {
          paymentError.textContent = 'Insufficient cash amount.';
          paymentError.style.display = 'block';
          return;
        }

        const violationIds = Array.from(document.querySelectorAll('.violation-checkbox:checked')).map(cb => cb.dataset.violationId);
        if (!violationIds.length) {
          paymentError.textContent = 'Please select at least one unpaid violation to pay.';
          paymentError.style.display = 'block';
          return;
        }

        const formData = new FormData();
        formData.append('citation_id', citationId);
        formData.append('amount', cash.toFixed(2));
        formData.append('csrf_token', csrfToken);
        violationIds.forEach(id => formData.append('violation_ids[]', id));

        loadingDiv.style.display = 'block';
        fetch('pay_citation.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
          const contentType = response.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
              throw new Error(`Unexpected content type: ${contentType}, Response: ${text}`);
            });
          }
          return response.json();
        })
        .then(data => {
          loadingDiv.style.display = 'none';
          if (data.status === 'success') {
            const receiptUrl = `receipt.php?citation_id=${encodeURIComponent(citationId)}&amount_paid=${encodeURIComponent(cash)}&change=${encodeURIComponent(data.change)}&payment_date=${encodeURIComponent(data.payment_date)}&reference_number=${encodeURIComponent(data.reference_number)}`;
            window.open(receiptUrl, '_blank');
            fetchTableData(searchInput.value, sortSelect.value, showArchived, currentPage, recordsPerPageSelect.value);
            hideModal(paymentModal);
            alert(data.message);
          } else {
            paymentError.textContent = data.message;
            paymentError.style.display = 'block';
          }
        })
        .catch(error => {
          loadingDiv.style.display = 'none';
          paymentError.textContent = 'Error processing payment: ' + error.message;
          paymentError.style.display = 'block';
          console.error('Payment fetch error:', error);
        });
      });

      document.getElementById('cancelPayment').addEventListener('click', () => {
        const paymentModal = document.getElementById('paymentModal');
        hideModal(paymentModal);
      });

      document.querySelector('#paymentModal .close').addEventListener('click', () => {
        const paymentModal = document.getElementById('paymentModal');
        hideModal(paymentModal);
      });

      let isPaymentModalClick = false;
      window.addEventListener('click', (event) => {
        const paymentModal = document.getElementById('paymentModal');
        if (event.target === paymentModal && !isPaymentModalClick) {
          isPaymentModalClick = true;
          hideModal(paymentModal);
        } else {
          isPaymentModalClick = false;
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.getElementById('paymentModal').classList.contains('show')) {
          const paymentModal = document.getElementById('paymentModal');
          hideModal(paymentModal);
        }
      });

      document.querySelectorAll('.column-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
          const columnIndex = checkbox.getAttribute('data-column');
          const cells = document.querySelectorAll(`#citationTable table th:nth-child(${parseInt(columnIndex) + 2}), #citationTable table td:nth-child(${parseInt(columnIndex) + 2})`);
          cells.forEach(cell => {
            cell.style.display = checkbox.checked ? '' : 'none';
          });
          localStorage.setItem(`column_${columnIndex}`, checkbox.checked);
        });
      });

      document.querySelectorAll('.column-toggle').forEach(checkbox => {
        const columnIndex = checkbox.getAttribute('data-column');
        const saved = localStorage.getItem(`column_${columnIndex}`);
        if (saved === 'false') {
          checkbox.checked = false;
          const cells = document.querySelectorAll(`#citationTable table th:nth-child(${parseInt(columnIndex) + 2}), #citationTable table td:nth-child(${parseInt(columnIndex) + 2})`);
          cells.forEach(cell => {
            cell.style.display = 'none';
          });
        }
      });
    };
  });
</script>