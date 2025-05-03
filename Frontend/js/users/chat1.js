import { toast, createModal, formatDateToMessage } from '../untils.js';
import config from '../config.js';

// Các biến toàn cục
let currentConversationId = null;
let currentPage = 1;
let isLoadingMessages = false;
const messageLimit = 20;
let isScrolledUp = false;
let selectedFiles = [];

// Biến toàn cục WebSocket
let ws = null;

// Hàm lấy người dùng trong localstorage
function getCurrentUser() {
  const userStr = localStorage.getItem('user');
  if (!userStr) return null;

  try {
    return JSON.parse(userStr);
  } catch (e) {
    console.error('Lỗi khi parse user từ localStorage:', e);
    return null;
  }
}

// Load tin nhắn
async function loadMessages(conversationId, isInitial = true) {
  const token = localStorage.getItem('access_token');
  if (!token) return;

  const currentUser = getCurrentUser();
  const chatContent = document.getElementById('chat-content');

  if (isInitial) {
    const chatHeader = document.getElementById('chat-header');
    chatHeader.dataset.id = conversationId;
    chatContent.innerHTML = '';
    currentPage = 1;
  }

  if (isLoadingMessages) return;
  isLoadingMessages = true;

  try {
    const response = await fetch(`${config.baseURL}/messages?page=${currentPage}&limit=${messageLimit}`, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      console.error('Lỗi khi gọi API lấy tin nhắn:', response.status);
      return;
    }

    const data = await response.json();
    const messages = data.messages || [];

    if (messages.length === 0) return;

    const oldScrollHeight = chatContent.scrollHeight;

    messages.forEach((msg) => {
      appendMessageToTop(msg, currentUser);
    });

    if (!isInitial) {
      const newScrollHeight = chatContent.scrollHeight;
      chatContent.scrollTop = newScrollHeight - oldScrollHeight;

      // Kiểm tra lại vị trí cuộn sau khi load thêm tin nhắn
      setTimeout(() => {
        checkScrollPosition();
      }, 100);
    } else {
      setTimeout(() => {
        chatContent.scrollTop = chatContent.scrollHeight;
      }, 0);
    }

    currentPage++;
  } catch (error) {
    console.error('Lỗi khi fetch tin nhắn:', error);
  } finally {
    isLoadingMessages = false;
  }
}

// Hàm tạo phần tử file đính kèm
function createAttachmentElement(att) {
  // Giả sử config.baseURL đã được import ở đầu file
  const fixedFileUrl = att.file_url.startsWith('http') ? att.file_url : `${config.baseURL}/${att.file_url.replace(/^\/+/, '')}`;
  const isImage = /\.(jpg|jpeg|png|gif)$/i.test(fixedFileUrl);
  if (isImage) {
    const img = document.createElement('img');
    img.src = fixedFileUrl;
    img.className = 'attachment-img';
    img.alt = 'Ảnh đính kèm';
    return img;
  }
  // Nếu là file khác
  const fileWrapper = document.createElement('div');
  fileWrapper.className = 'file-wrapper';
  fileWrapper.setAttribute('data-url', fixedFileUrl);
  const fileIcon = document.createElement('i');
  let iconClass = 'fa-file-alt';
  if (/\.(pdf)$/i.test(fixedFileUrl)) iconClass = 'fa-file-pdf';
  else if (/\.(mp4)$/i.test(fixedFileUrl)) iconClass = 'fa-file-video';
  else if (/\.(mp3)$/i.test(fixedFileUrl)) iconClass = 'fa-file-audio';
  fileIcon.className = `fas ${iconClass} file-icon`;
  fileWrapper.appendChild(fileIcon);
  const fileLink = document.createElement('a');
  fileLink.href = fixedFileUrl;
  fileLink.target = '_blank';
  fileLink.className = 'attachment-file';
  fileLink.textContent = att.file_url.split('/').pop();
  fileWrapper.appendChild(fileLink);
  return fileWrapper;
}

// Sửa appendMessageToUI để hiển thị file đính kèm
function appendMessageToUI(msg) {
  const currentUser = getCurrentUser();
  const chatContent = document.getElementById('chat-content');
  const isCurrentUser = msg.sender.user_id === currentUser.user_id;
  const messageDiv = document.createElement('div');
  messageDiv.classList.add('message', isCurrentUser ? 'sent' : 'received');
  messageDiv.dataset.messageId = msg.message_id;
  if (!isCurrentUser) {
    const senderName = document.createElement('div');
    senderName.className = 'sender-name';
    senderName.textContent = msg.sender.nickname || msg.sender.username;
    senderName.dataset.username = msg.sender.username;
    senderName.style.cursor = 'pointer';
    messageDiv.appendChild(senderName);
  }
  const textDiv = document.createElement('div');
  textDiv.className = 'message-text';
  textDiv.innerHTML = msg.content;
  messageDiv.appendChild(textDiv);
  // Hiển thị file đính kèm nếu có
  if (msg.attachments && msg.attachments.length > 0) {
    const attContainer = document.createElement('div');
    attContainer.className = 'attachments';
    msg.attachments.forEach((att) => {
      const attachmentEl = createAttachmentElement(att);
      attContainer.appendChild(attachmentEl);
    });
    messageDiv.appendChild(attContainer);
  }
  const time = document.createElement('span');
  time.className = 'timestamp';
  const date = new Date(msg.timestamp);
  date.setHours(date.getHours());
  time.innerHTML = formatDateToMessage(date);
  messageDiv.appendChild(time);
  chatContent.appendChild(messageDiv);
  chatContent.scrollTop = chatContent.scrollHeight;
}

// Sửa appendMessageToTop để hiển thị file đính kèm
function appendMessageToTop(msg, currentUser) {
  const chatContent = document.getElementById('chat-content');
  const isCurrentUser = msg.sender.user_id === currentUser.user_id;
  const messageDiv = document.createElement('div');
  messageDiv.classList.add('message', isCurrentUser ? 'sent' : 'received');
  messageDiv.dataset.messageId = msg.message_id;
  if (!isCurrentUser) {
    const senderName = document.createElement('div');
    senderName.className = 'sender-name';
    senderName.textContent = msg.sender.nickname || msg.sender.username;
    senderName.dataset.username = msg.sender.username;
    senderName.style.cursor = 'pointer';
    messageDiv.appendChild(senderName);
  }
  const textDiv = document.createElement('div');
  textDiv.className = 'message-text';
  textDiv.innerHTML = msg.content;
  messageDiv.appendChild(textDiv);
  // Hiển thị file đính kèm nếu có
  if (msg.attachments && msg.attachments.length > 0) {
    const attContainer = document.createElement('div');
    attContainer.className = 'attachments';
    msg.attachments.forEach((att) => {
      const attachmentEl = createAttachmentElement(att);
      attContainer.appendChild(attachmentEl);
    });
    messageDiv.appendChild(attContainer);
  }
  const time = document.createElement('span');
  time.className = 'timestamp';
  const date = new Date(msg.timestamp);
  date.setHours(date.getHours());
  time.innerHTML = formatDateToMessage(date);
  messageDiv.appendChild(time);
  chatContent.insertBefore(messageDiv, chatContent.firstChild);
}

function logout() {
  createModal({
    title: 'Xác nhận đăng xuất',
    message: 'Bạn có chắc chắn muốn đăng xuất không?',
    primaryButtonText: 'Đăng xuất',
    secondaryButtonText: 'Hủy',
    showSecondaryButton: true,
    onPrimary: () => {
      localStorage.clear();
      toast({
        title: 'Đăng xuất thành công!',
        message: 'Đang đăng xuất...',
        type: 'success',
      });
      setTimeout(() => {
        window.location.href = '../auth/login.html';
      }, 1500);
    },
  });
}

// Hàm kiểm tra vị trí cuộn và hiển thị nút
function checkScrollPosition() {
  const chatContent = document.getElementById('chat-content');
  const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');

  if (chatContent.scrollHeight - chatContent.scrollTop > chatContent.clientHeight + 50) {
    scrollToBottomBtn.style.display = 'block';
  } else {
    scrollToBottomBtn.style.display = 'none';
  }
}

// Hàm cuộn xuống dưới cùng
function scrollToBottom() {
  const chatContent = document.getElementById('chat-content');
  chatContent.scrollTo({
    top: chatContent.scrollHeight,
    behavior: 'smooth',
  });
  document.getElementById('scrollToBottomBtn').style.display = 'none';
}

// Hàm kết nối WebSocket
function connectWebSocket() {
  const user = getCurrentUser();
  if (!user) return;

  // Sử dụng config.wsUrl nếu có, nếu không thì mặc định ws://localhost:8080
  const wsUrl = (config.wsUrl || 'ws://localhost:8080') + `/ws/user/${user.username}`;
  ws = new WebSocket(wsUrl);

  ws.onopen = () => {
    console.log('WebSocket đã kết nối');
  };

  ws.onmessage = function (event) {
    try {
      const message = JSON.parse(event.data);
      const currentUser = getCurrentUser();
      // Nếu là tin nhắn của người khác thì mới append
      if (message && message.message_id && message.sender && message.sender.user_id !== currentUser.user_id) {
        appendMessageToUI(message);
      }
    } catch (error) {
      console.error('Lỗi khi parse message:', error);
    }
  };

  ws.onclose = function () {
    console.log('WebSocket đã đóng kết nối');
    setTimeout(connectWebSocket, 1000); // Tự động reconnect sau 1s
  };

  ws.onerror = function (error) {
    console.error('WebSocket error:', error);
  };
}

document.addEventListener('DOMContentLoaded', () => {
  const chatContent = document.getElementById('chat-content');
  const scrollToBottomBtn = document.getElementById('scrollToBottomBtn');

  // Load tin nhắn ban đầu
  loadMessages();

  // Thêm sự kiện cuộn để load thêm tin nhắn và kiểm tra vị trí cuộn
  chatContent.addEventListener('scroll', () => {
    if (chatContent.scrollTop === 0 && !isLoadingMessages) {
      loadMessages(currentConversationId, false);
    }
    checkScrollPosition();
  });

  // Thêm sự kiện click cho nút cuộn xuống
  scrollToBottomBtn.addEventListener('click', scrollToBottom);

  // Ẩn nút cuộn xuống ban đầu
  scrollToBottomBtn.style.display = 'none';

  // Gọi hàm kết nối WebSocket khi trang load
  connectWebSocket();
});

// Thêm hàm lấy và hiển thị thông tin người dùng
async function showUserInfo(username) {
  try {
    const token = localStorage.getItem('access_token');
    const response = await fetch(`${config.baseURL}/users/search?query=${username}&search_by_nickname=false`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    if (response.ok) {
      const data = await response.json();
      const userData = data.results[0]; // Lấy user đầu tiên từ mảng results

      if (userData) {
        const avatarUrl = userData.avatar
          ? userData.avatar.startsWith('http')
            ? userData.avatar
            : `${config.baseURL}/${userData.avatar.replace(/^\/+/, '')}`
          : '../../assets/image/private-chat-default.jpg';

        const modal = document.getElementById('user-info-modal');
        if (!modal) {
          console.error('Modal element not found');
          return;
        }

        modal.dataset.username = userData.username;
        modal.dataset.userId = userData.user_id;

        const avatarImg = document.getElementById('user-avatar');
        if (avatarImg) {
          avatarImg.src = avatarUrl;
          avatarImg.onerror = () => {
            avatarImg.src = '../../assets/image/private-chat-default.jpg';
          };
        }

        const usernameEl = document.getElementById('user-username');
        const nicknameEl = document.getElementById('user-nickname');
        const emailEl = document.getElementById('user-email');

        if (usernameEl) usernameEl.textContent = userData.username;
        if (nicknameEl) nicknameEl.textContent = userData.nickname || 'string';
        if (emailEl) emailEl.textContent = userData.email || 'user@example.com';

        modal.style.display = 'flex';
        console.log('Modal displayed for user:', userData.username);
      } else {
        console.log('No user data found');
      }
    }
  } catch (error) {
    console.error('Lỗi khi lấy thông tin người dùng:', error);
    toast({
      title: 'Lỗi',
      message: 'Không thể tải thông tin người dùng',
      type: 'error',
    });
  }
}

// Hàm đóng modal thông tin người dùng
function closeUserInfoModal() {
  const modal = document.getElementById('user-info-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Thêm vào window để có thể gọi từ HTML
window.closeUserInfoModal = closeUserInfoModal;

// Sự kiện click vào tên người gửi và tắt modal
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('sender-name')) {
    const username = e.target.dataset.username;
    console.log('Clicked on sender name:', username);
    if (username) {
      showUserInfo(username);
      console.log('Modal should be displayed now');
    }
  }
  // Sự kiện click vào ảnh đính kèm để mở modal xem ảnh lớn
  if (e.target.classList.contains('attachment-img')) {
    const modal = document.getElementById('image-modal');
    const modalImg = document.getElementById('modal-image');
    modal.style.display = 'flex';
    modalImg.src = e.target.src;
  }
  // Sự kiện tắt modal
  if (e.target.classList.contains('user-modal-close') || e.target.id === 'user-info-modal') {
    closeUserInfoModal();
  }
  if (e.target.classList.contains('close-btn') || e.target.id === 'image-modal') {
    document.getElementById('image-modal').style.display = 'none';
  }
});

window.logout = logout;

// Hàm gửi tin nhắn
async function sendMessage() {
  const token = localStorage.getItem('access_token');
  if (!token) {
    toast({
      title: 'Lỗi',
      message: 'Bạn chưa đăng nhập',
      type: 'error',
    });
    return;
  }

  const input = document.querySelector('.chat-input');
  const content = input.value.trim();

  // Nếu không có nội dung và không có file thì không gửi
  if (!content && selectedFiles.length === 0) {
    return;
  }

  try {
    const formData = new FormData();

    // Thêm nội dung tin nhắn nếu có
    if (content) {
      formData.append('content', content);
    }

    // Thêm file đã chọn (chỉ 1 file)
    if (selectedFiles.length > 0) {
      formData.append('file', selectedFiles[0]);
    }

    const response = await fetch(`${config.baseURL}/messages`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    });

    if (!response.ok) {
      throw new Error('Gửi tin nhắn thất bại');
    }

    const result = await response.json();
    console.log('Tin nhắn đã gửi:', result);

    // Xóa nội dung input và reset selectedFiles
    input.value = '';
    selectedFiles = [];

    // Thêm tin nhắn mới vào UI
    appendMessageToUI(result.data);

    // Cuộn xuống dưới cùng
    const chatContent = document.getElementById('chat-content');
    chatContent.scrollTop = chatContent.scrollHeight;

    // Nếu có gửi file thì hỏi reload
    if (selectedFiles.length > 0) {
      const shouldReload = confirm('Bạn vừa gửi tệp đính kèm.\nTải lại trang để cập nhật?');
      if (shouldReload) {
        window.location.reload();
      }
    }

    // Gửi tin nhắn qua WebSocket sau khi gửi thành công (nếu muốn)
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(result.data));
    }
  } catch (error) {
    console.error('Lỗi khi gửi tin nhắn:', error);
    toast({
      title: 'Lỗi',
      message: 'Không thể gửi tin nhắn',
      type: 'error',
    });
  }
}

// Hàm xử lý khi chọn ảnh
function sendImage(event) {
  const file = event.target.files[0];
  if (file) {
    selectedFiles = [file];
    sendMessage();
  }
}

// Hàm xử lý khi chọn file
function sendFile(event) {
  const file = event.target.files[0];
  if (file) {
    selectedFiles = [file];
    sendMessage();
  }
}

// Thêm sự kiện Enter để gửi tin nhắn
document.querySelector('.chat-input').addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

// Thêm vào window để có thể gọi từ HTML
window.sendMessage = sendMessage;
window.sendImage = sendImage;
window.sendFile = sendFile;
