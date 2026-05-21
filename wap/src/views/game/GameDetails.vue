<template>
  <div class="game-details-wrapper">
    <div class="docBody clearfix">
      <div id="mainBody">
        <div class="game_header clearfix">
          <div class="headerBox">
            <div class="det_t_bg">
              <div class="media-left">
                <i v-if="cpTypeid === 'k3'" class="iconfont">&#xe607;</i>
                <i v-else-if="cpTypeid === 'lhc'" class="iconfont" style="color:#07b39e">&#xe65a;</i>
                <i v-else-if="cpTypeid === 'ssc'" class="iconfont special">&#xe657;</i>
                <i v-else-if="cpTypeid === 'pk10'" class="icon--pk iconfont" style="color:#f22751"></i>
                <i v-else-if="cpTypeid === 'keno'" class="icon-kuaile8 iconfont" style="color:#fc5826"></i>
                <i v-else-if="cpTypeid === 'x5'" class="icon-11xuan5 iconfont" style="color:#218ddd"></i>
                <i v-else-if="cpTypeid === 'dpc'" :class="cpName.includes('3d') ? 'icon-fucai3d fc3d_c' : 'icon-pailie3 pl3_c'" class="iconfont" :style="{color: cpName.includes('3d') ? '#00b7ee' : '#38b366'}"></i>
              </div>
              
              <div class="clearfix titleBox">
                <h1>{{ detailData.cptitle }}</h1>
                <div class="abstract">
                  <span>Thời gian phát hành: {{ formatDate(detailData.oddtime) }}</span>
                  <span>Mã số: {{ detailData.trano }}</span>
                  <strong class="gameperiod">Kỳ số: {{ detailData.expect }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="topWrap clearfix">
          <div class="userInfoBox">
            <div class="clearfix userInfo">
              <img :src="userFace" style="display:inline;float:left;margin:0 15px 0 10px;border:1px solid #C2C2C2;height:60px;width:60px;">
              <div class="userName">
                Người phát hành <strong id="username">{{ maskUsername(detailData.username) }}</strong>
              </div>
            </div>
            <ul class="list clearfix">
              <li><span class="textL">Tuyên ngôn trúng thưởng: </span><strong id="word">{{ winningWords }}</strong></li>
            </ul>
          </div>
          
          <div class="scheme">
            <ul class="list">
              <li class="caseStatus" style="font-weight:900">Trạng thái phương án:
                <strong id="status">
                  <font :color="getStatusColor(detailData.isdraw)">{{ getStatusText(detailData.isdraw) }}</font>
                </strong>
              </li>
              <li>Tiến độ phương án:
                <div class="progressBox baodi">
                  <div class="progressBar">
                    <span :style="{width: (detailData.jindu * 100) + '%'}" class="progress">
                      <strong>{{ (detailData.jindu * 100).toFixed(0) }}</strong>%
                    </span>
                    <i :style="{right: (detailData.bdjindu * 100) + '%'}"></i>
                  </div>
                  <em v-if="detailData.isbaodi === 1" class="icoBaodi">Bảo đảm
                    <strong>{{ (detailData.bdjindu * 100).toFixed(0) }}%</strong>
                  </em>
                </div>
              </li>
              <li>Số tiền trúng thưởng: <strong id="winmoney"><font color="#FE5400">{{ detailData.okamount }}</font> đ</strong></li>
            </ul>
            
            <ul id="centerh" class="ulTable clearfix">
              <li><span>Tổng tiền</span><strong>{{ detailData.amount }} đ</strong></li>
              <li><span>Số phần còn lại</span><strong>{{ detailData.isfull }} phần</strong></li>
              <li><span>Tiền bảo đảm</span><strong>{{ getBaodiAmount() }}</strong></li>
              <li><span>Tổng số phần</span><strong>{{ detailData.fenshu }} phần</strong></li>
              <li style="border-right:0;"><span>Tiền mỗi phần</span><strong>{{ detailData.hemaipic }} đ</strong></li>
            </ul>
          </div>
          
          <div id="tzot" style="padding:10px">
            <table id="gaopinNumberTable" class="user_table" style="height:100px" width="100%" cellspacing="0" cellpadding="0" border="0">
              <thead>
                <tr><td>Số đặt cược</td></tr>
                <tr v-if="canShowCode">
                  <td style="background:#FFFFFF; padding:15px; text-align:center;">
                    [{{ detailData.playtitle }}]{{ detailData.tzcode }}
                  </td>
                </tr>
                <tr v-else>
                  <td><strong>{{ getCodeHiddenText() }}</strong></td>
                </tr>
              </thead>
            </table>
          </div>
          
          <div id="paybox" class="paybox">
            <div v-if="canBuy" id="touzhu">
              <p>
                Còn lại <strong class="c_ba2636" id="buyhave">{{ detailData.isfull }}</strong> phần Tôi muốn mua
                <input style="ime-mode: disabled;" size="5" id="buynum" name="buynum" v-model="buynum" :max="detailData.isfull" :placeholder="'Còn ' + detailData.isfull + ' phần'" class="input" @input="formatNumber" autocomplete="off"> phần.
              </p>
              <a id="addproject" href="javascript:;" rel="nofollow" class="betting_Btn" title="Đặt cược ngay" style="background: #a68f4c;" @click="checkProject">Đặt cược ngay</a>
              <input name="senumber" id="senumber" :value="detailData.isfull" type="hidden">
              <input name="onemoney" id="onemoney" :value="detailData.hemaipic" type="hidden">
              <input name="pid" id="pid" :value="detailData.id" type="hidden">
            </div>
            
            <div v-else-if="isStopped" id="jiezhi">
              <a class="end_Btn" href="javascript:;" rel="nofollow" title="Phương án đã hết hạn">Phương án đã hết hạn</a>
              <a target="_blank" href="/activity">Bạn có thể chọn tham gia hợp mãi khác >></a>
            </div>
            
            <div v-else id="manyuan">
              <a class="full_Btn" href="javascript:;" rel="nofollow" title="Phương án đã đủ người">Phương án đã đủ người</a>
              <a target="_blank" href="/activity">Bạn có thể chọn tham gia hợp mãi khác >></a>
            </div>
          </div>
        </div>
        
        <div class="number_user_wrap">
          <ul class="number_user_tab clearfix" id="joinTab">
            <li :class="{'an_cur': activeTab === 'details'}" @click="activeTab = 'details'">
              <a href="javascript:void(0);">Chi tiết kỳ</a>
            </li>
            <li :class="{'an_cur': activeTab === 'users'}" @click="activeTab = 'users'">
              <a href="javascript:void(0);">Người tham gia</a>
            </li>
          </ul>
          
          
          <div id="show_list_div" v-show="activeTab === 'details'">
            <table class="user_table hmxq" style="display: table;" width="100%" cellspacing="0" cellpadding="0" border="0">
              <thead>
                <tr>
                  <td width="10%">Kỳ số</td>
                  <td width="10%">Số tiền</td>
                  <td width="10%">Bội số</td>
                  <td width="10%">Số mở thưởng</td>
                  <td width="15%">Thời gian mở thưởng</td>
                  <td width="10%">Tiền thưởng</td>
                  <td width="10%">Trạng thái</td>
                  <td width="10%">Thao tác</td>
                </tr>
              </thead>
              <tbody class="testtest">
                <tr>
                  <td>{{ detailData.expect }}</td>
                  <td>￥{{ detailData.amount }}</td>
                  <td>{{ detailData.beishu }} bội</td>
                  <td>{{ detailData.opencode || '--' }}</td>
                  <td>{{ detailData.opentime ? formatDate(detailData.opentime) : '--' }}</td>
                  <td>{{ detailData.okamount }}</td>
                  <td class="red">
                    <b><font :color="getStatusColor(detailData.isdraw)">{{ getStatusText(detailData.isdraw) }}</font></b>
                  </td>
                  <td>--</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          
          <div v-show="activeTab === 'users'">
            <table class="user_table" style="display: table;" width="100%" cellspacing="0" cellpadding="0" border="0">
              <thead>
                <tr>
                  <th width="15%" style="text-align:center;">STT</th>
                  <th width="20%" style="text-align:center;">Tên người dùng</th>
                  <th width="20%" style="text-align:center;">Số tiền mua (đ)</th>
                  <th width="20%" style="text-align:center;">Tiền trúng thưởng (đ)</th>
                  <th width="25%" style="text-align:center;">Thời gian tham gia</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(user, index) in userList" :key="index">
                  <td>{{ index + 1 }}</td>
                  <td>{{ maskUsername(user.username) }}</td>
                  <td>{{ user.hemaipic * user.rengou }}</td>
                  <td>￥{{ user.okamount }}</td>
                  <td>{{ formatDate(user.oddtime) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showConfirmDialog" class="dialog-mask" @click="showConfirmDialog = false">
      <div class="dialog-content" @click.stop>
        <div class="dialog-title">Tôi muốn hợp mãi</div>
        <div class="dialog-body">
          <p style="margin: 0 0 -5px;font-weight: 600; font-size:14px;padding:.2em;">Xin chào, vui lòng xác nhận</p>
          <p style="margin: 0 0 -5px;text-align:left;text-indent:2em;font-weight: 400;font-size:14px;padding:.2em;">
            Số phần mua: <font color="red" style="font-weight:bold">{{ buynum }}</font> phần
          </p>
          <p style="margin: 0 0 -5px;text-align:left;text-indent:2em;font-weight: 400;font-size:14px;padding:.2em;">
            Số tiền mua: <font color="red" style="font-weight:bold">{{ (buynum * parseFloat(detailData.hemaipic)).toFixed(2) }}đ</font>
          </p>
        </div>
        <div class="dialog-footer">
          <button @click="showConfirmDialog = false">Hủy</button>
          <button @click="submitBuy" class="primary">Xác nhận</button>
        </div>
      </div>
    </div>
    
    <slot name="footer"></slot>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const cpTypeid = ref('k3')
const cpName = ref('k3')
const userFace = ref('/assets/images/common/rui-face.png')
const winningWords = ref('Số đơn giản tạo nên giấc mơ giải lớn')
const activeTab = ref('details')
const buynum = ref(1)
const showConfirmDialog = ref(false)

const detailData = ref({
  id: 1,
  cptitle: '快三',
  trano: 'HM20240101001',
  expect: '20240101001',
  oddtime: new Date().getTime() / 1000,
  username: '测试用户',
  isdraw: 0,
  jindu: 0.5,
  bdjindu: 0.2,
  isbaodi: 1,
  okamount: '0.00',
  amount: '1000.00',
  isfull: 50,
  baodi: 0.2,
  fenshu: 100,
  hemaipic: '10.00',
  playtitle: '三星直选',
  tzcode: '123,456,789',
  showtype: 0,
  beishu: 1,
  opencode: '',
  opentime: 0
})

const userList = ref([
  {
    username: '用户1',
    hemaipic: 10,
    rengou: 5,
    okamount: '0.00',
    oddtime: new Date().getTime() / 1000
  },
  {
    username: '用户2',
    hemaipic: 10,
    rengou: 3,
    okamount: '0.00',
    oddtime: new Date().getTime() / 1000
  }
])

const currentUser = ref({
  username: 'testuser'
})

const inList = ref(false)
const isStopped = ref(false)

const canBuy = computed(() => {
  return detailData.value.isfull > 0 && !isStopped.value
})

const canShowCode = computed(() => {
  const { showtype, username, isdraw } = detailData.value
  
  if (showtype === 0) return true // 完全公开
  if (showtype === 1) {

    return username === currentUser.value.username || isdraw === 1 || isdraw === -1 || isdraw === -2
  }
  if (showtype === 2) {

    return inList.value
  }
  if (showtype === 3) {

    return username === currentUser.value.username
  }
  return false
})

const getCodeHiddenText = () => {
  const { showtype } = detailData.value
  
  if (showtype === 1) {
    return "Phương án này chọn công khai sau khi mở thưởng"
  }
  if (showtype === 2) {
    return "Phương án này chọn hiển thị khi tham gia (chỉ công khai cho người tham gia)"
  }
  if (showtype === 3) {
    return "Phương án này chọn bảo mật vĩnh viễn (chỉ người phát hành mới thấy)"
  }
  return "Số đã bị ẩn"
}

const getStatusText = (isdraw) => {
  const statusMap = {
    0: 'Chưa mở thưởng',
    1: 'Đã trúng thưởng',
    '-1': 'Không trúng',
    '-2': 'Đã hủy'
  }
  return statusMap[isdraw] || 'Không rõ'
}

const getStatusColor = (isdraw) => {
  const colorMap = {
    0: '#0091D1',
    1: '#FF070B',
    '-1': '#727171',
    '-2': '#727171'
  }
  return colorMap[isdraw] || '#000000'
}

const getBaodiAmount = () => {
  if (detailData.value.isbaodi === 1) {
    return (detailData.value.baodi * detailData.value.hemaipic).toFixed(2) + 'đ'
  }
  return 'Không bảo đảm'
}

const formatDate = (timestamp) => {
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')
  const second = String(date.getSeconds()).padStart(2, '0')
  
  return `${year}-${month}-${day} ${hour}:${minute}:${second}`
}

const maskUsername = (username) => {
  if (!username || username.length < 2) return username
  return username.substring(0, 2) + '****'
}

const formatNumber = (e) => {
  buynum.value = buynum.value.toString().replace(/[^\d]/g, '')
}

const checkProject = () => {
  const num = parseInt(buynum.value)
  
  if (!buynum.value) {
    window.alert('Số phần mua không được để trống!')
    return
  }
  
  if (num <= 0) {
    window.alert('Số phần mua không được nhỏ hơn 1!')
    return
  }
  
  if (num > detailData.value.isfull) {
    window.alert('Số phần mua không được lớn hơn số phần còn lại!')
    return
  }
  
  showConfirmDialog.value = true
}

const submitBuy = async () => {
  showConfirmDialog.value = false
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    window.alert('Phát hành hợp mãi thành công')

    detailData.value.isfull -= parseInt(buynum.value)
    detailData.value.jindu = (detailData.value.fenshu - detailData.value.isfull) / detailData.value.fenshu
    buynum.value = 1
  } catch (error) {
    window.alert('Giao dịch thất bại, vui lòng thử lại!')
  }
}

const fetchDetails = async () => {
  const id = route.params.id
  if (!id) return
  
  try {

    await new Promise(resolve => setTimeout(resolve, 500))

  } catch (error) {
  }
}

onMounted(() => {
  fetchDetails()
})
</script>

<style scoped>
.game-details-wrapper {
  min-height: 100vh;
  background: #f5f5f5;
  font-family: Arial, sans-serif;
}

.docBody {
  max-width: 1200px;
  margin: 0 auto;
  background: #fff;
  padding: 20px;
}

.game_header {
  margin-bottom: 20px;
}

.det_t_bg {
  display: flex;
  align-items: center;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-radius: 8px;
}

.media-left {
  margin-right: 20px;
}

.media-left i {
  font-size: 48px;
}

.titleBox h1 {
  margin: 0 0 10px 0;
  font-size: 24px;
}

.abstract span {
  margin-right: 15px;
  font-size: 14px;
}

.topWrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.userInfoBox,
.scheme {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 15px;
}

.userInfo {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

.userName {
  font-size: 16px;
}

.userName strong {
  color: #667eea;
  margin-left: 5px;
}

.list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.list li {
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.progressBox {
  margin: 10px 0;
  position: relative;
}

.progressBar {
  height: 30px;
  background: #e0e0e0;
  border-radius: 15px;
  position: relative;
  overflow: hidden;
}

.progress {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 15px;
  text-align: center;
  line-height: 30px;
  color: #fff;
  transition: width 0.3s;
}

.icoBaodi {
  position: absolute;
  right: 0;
  top: 35px;
  background: #ff9800;
  color: #fff;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.ulTable {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
  list-style: none;
  padding: 0;
  margin: 15px 0 0 0;
}

.ulTable li {
  text-align: center;
  border-right: 1px solid #e0e0e0;
  padding: 10px;
}

.ulTable span {
  display: block;
  color: #999;
  font-size: 12px;
  margin-bottom: 5px;
}

.ulTable strong {
  color: #333;
  font-size: 14px;
}

.user_table {
  width: 100%;
  border-collapse: collapse;
  margin: 15px 0;
}

.user_table thead {
  background: #f5f5f5;
}

.user_table td,
.user_table th {
  padding: 12px;
  border: 1px solid #e0e0e0;
  text-align: center;
}

.paybox {
  margin: 20px 0;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 8px;
  text-align: center;
}

.input {
  width: 80px;
  padding: 5px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  text-align: center;
  margin: 0 10px;
}

.betting_Btn,
.end_Btn,
.full_Btn {
  display: inline-block;
  padding: 10px 30px;
  background: #a68f4c;
  color: #fff;
  border-radius: 4px;
  text-decoration: none;
  margin: 10px 5px;
  cursor: pointer;
  border: none;
  font-size: 16px;
}

.end_Btn {
  background: #999;
  cursor: not-allowed;
}

.full_Btn {
  background: #666;
  cursor: not-allowed;
}

.number_user_tab {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 20px 0 0 0;
  border-bottom: 2px solid #e0e0e0;
}

.number_user_tab li {
  flex: 1;
  text-align: center;
}

.number_user_tab li a {
  display: block;
  padding: 15px;
  text-decoration: none;
  color: #666;
  transition: all 0.3s;
}

.number_user_tab li.an_cur a {
  color: #667eea;
  border-bottom: 2px solid #667eea;
}

.c_ba2636 {
  color: #ba2636;
}

.dialog-mask {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog-content {
  background: #fff;
  border-radius: 8px;
  width: 90%;
  max-width: 400px;
  padding: 20px;
}

.dialog-title {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 15px;
  text-align: center;
}

.dialog-body {
  margin: 15px 0;
}

.dialog-footer {
  display: flex;
  justify-content: space-around;
  margin-top: 20px;
}

.dialog-footer button {
  padding: 10px 30px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  font-size: 14px;
}

.dialog-footer button.primary {
  background: #667eea;
  color: #fff;
  border-color: #667eea;
}

.clearfix::after {
  content: '';
  display: block;
  clear: both;
}
</style>
