import config from '../config.js';
import { toast } from '../untils.js';

document.addEventListener('DOMContentLoaded', function () {
  const token = localStorage.getItem('access_token');

  fetch(`${config.baseURL}/admin/users`, {
    method: 'GET',
    headers: {
      Authorization: 'Bearer ' + token,
      'Content-Type': 'application/json',
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.users && Array.isArray(data.users)) {
        const tbody = document.querySelector('#user-table tbody');
        tbody.innerHTML = ''; // Xóa dữ liệu cũ nếu có
        data.users.forEach((user) => {
          const tr = document.createElement('tr');
          let actionBtns = '';
          if (!user.is_admin) {
            actionBtns = `
              <button class="btn-admin" data-id="${user.user_id}">Chỉ định admin</button>
              <button class="btn-delete" data-id="${user.user_id}">Xóa tài khoản</button>
            `;
          }
          tr.innerHTML = `
            <td>${user.user_id}</td>
            <td>${user.username}</td>
            <td>${user.nickname}</td>
            <td>${user.email}</td>
            <td>${user.is_admin ? 'Admin' : 'User'}</td>
            <td>${user.last_active_UTC || ''}</td>
            <td>${user.created_at_UTC || ''}</td>
            <td>${actionBtns}</td>
          `;
          tbody.appendChild(tr);
        });

        // Gán sự kiện cho nút "Chỉ định admin"
        tbody.querySelectorAll('.btn-admin').forEach((btn) => {
          btn.addEventListener('click', function () {
            const userId = this.getAttribute('data-id');
            fetch(`${config.baseURL}/admin/set-admin/${userId}`, {
              method: 'POST',
              headers: {
                Authorization: 'Bearer ' + token,
                'Content-Type': 'application/json',
              },
            })
              .then((res) => res.json())
              .then((result) => {
                toast({
                  title: 'Thông báo',
                  message: result.message || 'Đã cấp quyền admin!',
                  type: 'success',
                  duration: 3000,
                });
                setTimeout(() => location.reload(), 1500); // Reload lại danh sách user sau khi toast
              })
              .catch((err) => {
                toast({
                  title: 'Lỗi',
                  message: 'Có lỗi xảy ra!',
                  type: 'error',
                  duration: 3000,
                });
              });
          });
        });
        // Gán sự kiện cho nút "Xóa tài khoản"
        tbody.querySelectorAll('.btn-delete').forEach((btn, idx) => {
          btn.addEventListener('click', function () {
            const userId = this.getAttribute('data-id');

            if (confirm('Bạn có chắc muốn xóa tài khoản này không?')) {
              fetch(`${config.baseURL}/admin/delete-user/${userId}`, {
                method: 'DELETE',
                headers: {
                  Authorization: 'Bearer ' + token,
                  'Content-Type': 'application/json',
                },
              })
                .then((res) => res.json())
                .then((result) => {
                  toast({
                    title: 'Thông báo',
                    message: result.message || 'Đã xóa tài khoản!',
                    type: 'success',
                    duration: 3000,
                  });
                  setTimeout(() => location.reload(), 1500);
                })
                .catch((err) => {
                  toast({
                    title: 'Lỗi',
                    message: 'Có lỗi xảy ra!',
                    type: 'error',
                    duration: 3000,
                  });
                });
            }
          });
        });
      }
    })
    .catch((error) => {
      console.error('Lỗi khi lấy danh sách người dùng:', error);
    });
});
