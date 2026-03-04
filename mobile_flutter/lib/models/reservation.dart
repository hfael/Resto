class Reservation {
  const Reservation({
    required this.id,
    required this.restaurantId,
    required this.userId,
    required this.reservationDate,
    required this.reservationTime,
    required this.code,
    this.restaurantName,
    this.username,
    this.email,
  });

  final int id;
  final int restaurantId;
  final int userId;
  final String reservationDate;
  final String reservationTime;
  final String code;
  final String? restaurantName;
  final String? username;
  final String? email;

  factory Reservation.fromJson(Map<String, dynamic> json) {
    return Reservation(
      id: (json['id'] as num?)?.toInt() ?? 0,
      restaurantId: (json['restaurant_id'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      reservationDate: (json['reservation_date'] ?? '') as String,
      reservationTime: (json['reservation_time'] ?? '') as String,
      code: (json['code'] ?? '') as String,
      restaurantName: json['restaurant_name'] as String?,
      username: json['username'] as String?,
      email: json['email'] as String?,
    );
  }
}
